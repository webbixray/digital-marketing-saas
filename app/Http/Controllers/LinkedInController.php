<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\LinkedInApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LinkedInController extends Controller
{
    public function __construct(
        private readonly LinkedInApiService $linkedin,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show LinkedIn integration dashboard.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $linkedinAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'linkedin')
            ->active()
            ->get();

        return view('linkedin.index', compact('linkedinAccounts'));
    }

    /**
     * Connect a LinkedIn account via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $state = Str::random(32);
        $request->session()->put('linkedin_state', $state);
        $request->session()->put('linkedin_connect_agency_id', $agencyId);

        $authUrl = $this->linkedin->getAuthUrl(
            route('linkedin.callback'),
            $state
        );

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from LinkedIn.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== $request->session()->get('linkedin_state')) {
            return redirect()->route('linkedin.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            Log::error('LinkedIn OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);
            return redirect()->route('linkedin.index')
                ->with('error', 'LinkedIn authorization failed: ' . $request->get('error_description'));
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('linkedin.index')
                ->with('error', 'No authorization code received.');
        }

        $tokenResult = $this->linkedin->exchangeCodeForToken($code, route('linkedin.callback'));

        if (!$tokenResult['success']) {
            Log::error('LinkedIn token exchange failed', $tokenResult);
            return redirect()->route('linkedin.index')
                ->with('error', 'Failed to connect LinkedIn: ' . ($tokenResult['error'] ?? 'Unknown error'));
        }

        $accessToken = $tokenResult['access_token'];
        $expiresIn = $tokenResult['expires_in'] ?? null;

        // Get user profile
        $profileResult = $this->linkedin->getUserProfile($accessToken);

        if (!$profileResult['success']) {
            return redirect()->route('linkedin.index')
                ->with('error', 'Failed to fetch LinkedIn profile.');
        }

        $profile = $profileResult['data'];
        $agencyId = $request->session()->get('linkedin_connect_agency_id');

        // Check if account is already connected
        $existingAccount = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'linkedin')
            ->where('platform_account_id', $profile['sub'] ?? '')
            ->first();

        $accountData = [
            'platform' => 'linkedin',
            'platform_account_id' => $profile['sub'] ?? null,
            'platform_username' => $profile['name'] ?? null,
            'platform_display_name' => $profile['name'] ?? null,
            'platform_account_type' => 'personal',
            'access_token' => $accessToken,
            'refresh_token' => $tokenResult['refresh_token'] ?? null,
            'token_type' => 'bearer',
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'metadata' => [
                'email' => $profile['email'] ?? null,
                'picture' => $profile['picture'] ?? null,
            ],
            'is_active' => true,
            'is_verified' => false,
        ];

        if ($existingAccount) {
            $existingAccount->update($accountData);
        } else {
            SocialAccount::create(array_merge($accountData, [
                'agency_id' => $agencyId,
            ]));
        }

        $request->session()->forget(['linkedin_state', 'linkedin_connect_agency_id']);

        return redirect()->route('linkedin.index')
            ->with('success', 'LinkedIn account connected successfully!');
    }

    /**
     * Disconnect a LinkedIn account.
     */
    public function disconnect(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('platform', 'linkedin')
            ->find($accountId);

        if (!$account) {
            abort(404, 'LinkedIn account not found.');
        }

        if ((int) $account->agency_id !== (int) $agencyId) {
            abort(403, 'You do not have permission to disconnect this account.');
        }

        $account->delete();

        return redirect()->route('linkedin.index')
            ->with('success', 'LinkedIn account disconnected.');
    }

    /**
     * Toggle account active status.
     */
    public function toggle(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'linkedin')
            ->findOrFail($accountId);

        $account->update(['is_active' => !$account->is_active]);

        return redirect()->route('linkedin.index')
            ->with('success', 'LinkedIn account status updated.');
    }

    /**
     * Get LinkedIn account metrics.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'linkedin')
            ->findOrFail($accountId);

        if (!$account->isExpired()) {
            $profile = $this->linkedin->getUserProfile($account->access_token);
        } else {
            $profile = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
        }

        return view('linkedin.metrics', compact('account', 'profile'));
    }

    /**
     * Refresh LinkedIn token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'linkedin')
            ->findOrFail($accountId);

        if (!$account->refresh_token) {
            return redirect()->route('linkedin.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->linkedin->refreshToken($account->refresh_token);

        if (!$result['success']) {
            return redirect()->route('linkedin.index')
                ->with('error', 'Token refresh failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $account->update([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in']),
        ]);

        return redirect()->route('linkedin.index')
            ->with('success', 'Token refreshed successfully!');
    }

    /**
     * API: Share to LinkedIn.
     */
    public function apiShare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'text' => 'required|string|max:3000',
            'media_url' => 'nullable|url',
            'media_type' => 'nullable|in:article',
            'title' => 'nullable|string|max:256',
            'description' => 'nullable|string|max=256',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'linkedin')
            ->first();

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        if ($account->isExpired()) {
            return response()->json(['error' => 'Token expired. Please reconnect.'], 401);
        }

        $authorUrn = 'urn:li:person:' . $account->platform_account_id;

        $options = [];
        if (!empty($validated['media_url'])) {
            $options['media_url'] = $validated['media_url'];
            $options['media_type'] = $validated['media_type'] ?? 'article';
            $options['title'] = $validated['title'] ?? '';
            $options['description'] = $validated['description'] ?? '';
        }

        $result = $this->linkedin->share($account->access_token, $authorUrn, $validated['text'], $options);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'post_id' => $result['post_id'],
        ]);
    }
}
