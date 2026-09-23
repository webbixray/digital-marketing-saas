<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shared OAuth connection lifecycle methods for social platform controllers.
 * Consolidates identical connect/callback/disconnect/toggle logic from
 * Facebook, Instagram, LinkedIn, Pinterest, TikTok, YouTube controllers.
 *
 * Controllers using this trait MUST define:
 * - protected string $oauthPlatform;
 * - protected string $oauthPlatformName;
 * - protected string $oauthRoutePrefix;
 */
trait SocialOAuthConnectTrait
{
    /**
     * Connect an account via OAuth — generates state and redirects to platform auth URL.
     *
     * @param callable $authUrlFn function(string $redirectUri, string $state): string
     */
    protected function oauthConnect(Request $request, callable $authUrlFn): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $state = Str::random(32);
        $request->session()->put("{$this->oauthPlatform}_state", $state);
        $request->session()->put("{$this->oauthPlatform}_connect_agency_id", $agencyId);

        $authUrl = $authUrlFn(route("{$this->oauthRoutePrefix}.callback"), $state);

        Log::info("{$this->oauthPlatformName} OAuth initiated", ['agency_id' => $agencyId]);

        return redirect($authUrl);
    }

    /**
     * Handle common OAuth callback verification (state + error checks).
     * Returns null on success, or a RedirectResponse on failure.
     */
    protected function oauthVerifyCallback(Request $request): ?RedirectResponse
    {
        if ($request->get('state') !== $request->session()->get("{$this->oauthPlatform}_state")) {
            Log::warning("{$this->oauthPlatformName} OAuth: invalid state", [
                'agency_id' => $request->user()->agency_id,
            ]);
            return redirect()->route("{$this->oauthRoutePrefix}.index")
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            Log::error("{$this->oauthPlatformName} OAuth error", [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
                'agency_id' => $request->user()->agency_id,
            ]);
            return redirect()->route("{$this->oauthRoutePrefix}.index")
                ->with('error', "{$this->oauthPlatformName} authorization failed: " . $request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route("{$this->oauthRoutePrefix}.index")
                ->with('error', 'No authorization code received.');
        }

        return null;
    }

    /**
     * Clean up OAuth session state after callback.
     */
    protected function oauthCleanupSession(Request $request): void
    {
        $request->session()->forget([
            "{$this->oauthPlatform}_state",
            "{$this->oauthPlatform}_connect_agency_id",
        ]);
    }

    /**
     * Disconnect a platform account — checks ownership then deletes.
     */
    protected function oauthDisconnect(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('platform', $this->oauthPlatform)
            ->find($accountId);

        if (! $account) {
            abort(404, "{$this->oauthPlatformName} account not found.");
        }

        if ((int) $account->agency_id !== (int) $agencyId) {
            Log::warning("{$this->oauthPlatformName} disconnect: permission denied", [
                'account_id' => $accountId,
                'agency_id' => $agencyId,
            ]);
            abort(403, 'You do not have permission to disconnect this account.');
        }

        $account->delete();

        Log::info("{$this->oauthPlatformName} account disconnected", [
            'account_id' => $accountId,
            'agency_id' => $agencyId,
        ]);

        return redirect()->route("{$this->oauthRoutePrefix}.index")
            ->with('success', "{$this->oauthPlatformName} account disconnected.");
    }

    /**
     * Toggle account active status.
     */
    protected function oauthToggle(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->oauthPlatform)
            ->findOrFail($accountId);

        $newStatus = ! $account->is_active;
        $account->update(['is_active' => $newStatus]);

        Log::info("{$this->oauthPlatformName} account status toggled", [
            'account_id' => $accountId,
            'agency_id' => $agencyId,
            'new_status' => $newStatus ? 'active' : 'inactive',
        ]);

        return redirect()->route("{$this->oauthRoutePrefix}.index")
            ->with('success', "{$this->oauthPlatformName} account status updated.");
    }
}
