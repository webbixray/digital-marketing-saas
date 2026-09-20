<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

trait PlatformOAuthTrait
{
    /**
     * The platform identifier string (e.g., 'facebook', 'instagram').
     */
    protected string $platform;

    /**
     * The platform display name for error messages.
     */
    protected string $platformName;

    /**
     * The route name prefix for redirects (e.g., 'facebook.index').
     */
    protected string $routePrefix;

    /**
     * The view path prefix for platform views.
     */
    protected string $viewPrefix;

    /**
     * Show platform integration dashboard with active accounts.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $accounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->active()
            ->get();

        return view($this->viewPrefix . '.index', [$this->platform . 'Accounts' => $accounts]);
    }

    /**
     * Connect an account via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $state = Str::random(32);
        $request->session()->put($this->platform . '_state', $state);
        $request->session()->put($this->platform . '_connect_agency_id', $agencyId);

        $authUrl = $this->getAuthUrl(route($this->routePrefix . '.callback'), $state);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Verify state
        if ($request->get('state') !== $request->session()->get($this->platform . '_state')) {
            return redirect()->route($this->routePrefix . '.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        // Check for OAuth errors
        if ($request->has('error')) {
            Log::error(ucfirst($this->platform) . ' OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);

            return redirect()->route($this->routePrefix . '.index')
                ->with('error', $this->platformName . ' authorization failed: ' . $request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route($this->routePrefix . '.index')
                ->with('error', 'No authorization code received.');
        }

        // Exchange code for token
        $tokenResult = $this->exchangeCodeForToken($code, route($this->routePrefix . '.callback'));

        if (! $tokenResult['success']) {
            Log::error(ucfirst($this->platform) . ' token exchange failed', $tokenResult);

            return redirect()->route($this->routePrefix . '.index')
                ->with('error', 'Failed to connect ' . $this->platformName . ': ' . ($tokenResult['error'] ?? 'Unknown error'));
        }

        // Fetch platform-specific account data
        $accountData = $this->fetchAccountData($tokenResult);

        if (! $accountData['success']) {
            return redirect()->route($this->routePrefix . '.index')
                ->with('error', $accountData['error']);
        }

        $agencyId = $request->session()->get($this->platform . '_connect_agency_id');

        // Check for existing account
        $existingAccount = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->where('platform_account_id', $accountData['platform_account_id'])
            ->first();

        $socialAccountData = array_merge([
            'agency_id' => $agencyId,
            'platform' => $this->platform,
            'platform_account_type' => $accountData['platform_account_type'] ?? 'personal',
            'access_token' => $tokenResult['access_token'],
            'refresh_token' => $tokenResult['refresh_token'] ?? null,
            'token_type' => 'bearer',
            'token_expires_at' => isset($tokenResult['expires_in'])
                ? now()->addSeconds($tokenResult['expires_in'])
                : null,
            'is_active' => true,
            'is_verified' => false,
        ], $accountData);

        unset($socialAccountData['success'], $socialAccountData['error']);

        if ($existingAccount) {
            $existingAccount->update($socialAccountData);
        } else {
            SocialAccount::create($socialAccountData);
        }

        $request->session()->forget([$this->platform . '_state', $this->platform . '_connect_agency_id']);

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', $this->platformName . ' account connected successfully!');
    }

    /**
     * Disconnect a platform account.
     */
    public function disconnect(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('platform', $this->platform)
            ->find($accountId);

        if (! $account) {
            abort(404, $this->platformName . ' account not found.');
        }

        if ((int) $account->agency_id !== (int) $agencyId) {
            abort(403, 'You do not have permission to disconnect this account.');
        }

        $account->delete();

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', $this->platformName . ' account disconnected.');
    }

    /**
     * Toggle account active status.
     */
    public function toggle(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->findOrFail($accountId);

        $account->update(['is_active' => ! $account->is_active]);

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', $this->platformName . ' account status updated.');
    }

    /**
     * Refresh platform token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->findOrFail($accountId);

        if (! $account->refresh_token) {
            return redirect()->route($this->routePrefix . '.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->performTokenRefresh($account->refresh_token);

        if (! $result['success']) {
            return redirect()->route($this->routePrefix . '.index')
                ->with('error', 'Token refresh failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $updateData = [
            'access_token' => $result['access_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in']),
        ];

        if (isset($result['refresh_token'])) {
            $updateData['refresh_token'] = $result['refresh_token'];
        }

        $account->update($updateData);

        return redirect()->route($this->routePrefix . '.index')
            ->with('success', 'Token refreshed successfully!');
    }

    /**
     * Get account metrics for a platform account.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->findOrFail($accountId);

        if (! $account->isExpired()) {
            $metricsData = $this->fetchMetrics($account);
        } else {
            $metricsData = $this->getExpiredTokenMetrics();
        }

        return view($this->viewPrefix . '.metrics', array_merge(
            ['account' => $account],
            $metricsData
        ));
    }

    // ============================================================================
    // Abstract Methods - Platform-specific implementations
    // ============================================================================

    /**
     * Get the authorization URL for the platform.
     */
    abstract protected function getAuthUrl(string $redirectUri, string $state): string;

    /**
     * Exchange authorization code for access token.
     */
    abstract protected function exchangeCodeForToken(string $code, string $redirectUri): array;

    /**
     * Fetch platform-specific account data after token exchange.
     * Returns array with keys: success, platform_account_id, platform_username,
     * platform_display_name, platform_account_type, metadata, [error if failed].
     */
    abstract protected function fetchAccountData(array $tokenResult): array;

    /**
     * Refresh the access token.
     */
    abstract protected function performTokenRefresh(string $refreshToken): array;

    /**
     * Fetch metrics for an active account.
     * Returns associative array for view data.
     */
    abstract protected function fetchMetrics(SocialAccount $account): array;

    // ============================================================================
    // Default Implementations
    // ============================================================================

    /**
     * Get expired token metrics data.
     */
    protected function getExpiredTokenMetrics(): array
    {
        return [
            'insights' => ['success' => false, 'error' => 'Token expired. Please reconnect.'],
            'profile' => ['success' => false, 'error' => 'Token expired. Please reconnect.'],
        ];
    }

    /**
     * Find a social account by platform and agency.
     */
    protected function findAccount(int $agencyId, int $accountId): ?SocialAccount
    {
        return SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->find($accountId);
    }

    /**
     * Find a social account or fail.
     */
    protected function findAccountOrFail(int $agencyId, int $accountId): SocialAccount
    {
        return SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform)
            ->findOrFail($accountId);
    }

    /**
     * Get all accounts for an agency and platform.
     */
    protected function getAccountsForAgency(int $agencyId, bool $activeOnly = false)
    {
        $query = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', $this->platform);

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }
}
