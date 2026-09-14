<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\FacebookApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FacebookController extends Controller
{
    public function __construct(
        private readonly FacebookApiService $facebook,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show Facebook integration dashboard.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $facebookAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'facebook')
            ->active()
            ->get();

        return view('facebook.index', compact('facebookAccounts'));
    }

    /**
     * Connect a Facebook Page via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        // Generate state and store in session
        $state = Str::random(32);
        $request->session()->put('facebook_state', $state);
        $request->session()->put('facebook_connect_agency_id', $agencyId);

        $authUrl = $this->facebook->getAuthUrl(
            route('facebook.callback'),
            $state
        );

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Facebook.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Verify state
        if ($request->get('state') !== $request->session()->get('facebook_state')) {
            return redirect()->route('facebook.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        // Check for errors
        if ($request->has('error')) {
            Log::error('Facebook OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);

            return redirect()->route('facebook.index')
                ->with('error', 'Facebook authorization failed: '.$request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('facebook.index')
                ->with('error', 'No authorization code received.');
        }

        // Exchange code for token
        $tokenResult = $this->facebook->exchangeCodeForToken($code, route('facebook.callback'));

        if (! $tokenResult['success']) {
            Log::error('Facebook token exchange failed', $tokenResult);

            return redirect()->route('facebook.index')
                ->with('error', 'Failed to connect Facebook: '.($tokenResult['error'] ?? 'Unknown error'));
        }

        $accessToken = $tokenResult['access_token'];
        $expiresIn = $tokenResult['expires_in'] ?? null;

        // Get Facebook Pages
        $pagesResult = $this->facebook->getPages($accessToken);

        if (! $pagesResult['success'] || empty($pagesResult['pages'])) {
            return redirect()->route('facebook.index')
                ->with('error', 'No Facebook Pages found. Please ensure you have admin access to a Facebook Page.');
        }

        $agencyId = $request->session()->get('facebook_connect_agency_id');

        // Connect each page
        $connectedCount = 0;
        foreach ($pagesResult['pages'] as $page) {
            // Check if account is already connected
            $existingAccount = SocialAccount::where('agency_id', $agencyId)
                ->where('platform', 'facebook')
                ->where('platform_account_id', $page['id'])
                ->first();

            $accountData = [
                'platform' => 'facebook',
                'platform_account_id' => $page['id'],
                'platform_username' => $page['name'] ?? null,
                'platform_display_name' => $page['name'] ?? null,
                'platform_account_type' => $page['category'] ?? 'page',
                'access_token' => $page['access_token'],
                'refresh_token' => $accessToken,
                'token_type' => 'bearer',
                'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                'metadata' => [
                    'category' => $page['category'] ?? null,
                    'fan_count' => $page['fan_count'] ?? 0,
                    'link' => $page['link'] ?? null,
                    'picture_url' => $page['picture']['data']['url'] ?? null,
                    'about' => $page['about'] ?? null,
                    'website' => $page['website'] ?? null,
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

            $connectedCount++;
        }

        $request->session()->forget(['facebook_state', 'facebook_connect_agency_id']);

        return redirect()->route('facebook.index')
            ->with('success', "{$connectedCount} Facebook Page(s) connected successfully!");
    }

    /**
     * Disconnect a Facebook account.
     */
    public function disconnect(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        // Check if account exists at all
        $account = SocialAccount::where('platform', 'facebook')
            ->find($accountId);

        if (! $account) {
            abort(404, 'Facebook account not found.');
        }

        // Check ownership
        if ((int) $account->agency_id !== (int) $agencyId) {
            abort(403, 'You do not have permission to disconnect this account.');
        }

        $account->delete();

        return redirect()->route('facebook.index')
            ->with('success', 'Facebook account disconnected.');
    }

    /**
     * Toggle account active status.
     */
    public function toggle(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'facebook')
            ->findOrFail($accountId);

        $account->update(['is_active' => ! $account->is_active]);

        return redirect()->route('facebook.index')
            ->with('success', 'Facebook account status updated.');
    }

    /**
     * Get Facebook account metrics.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'facebook')
            ->findOrFail($accountId);

        if (! $account->isExpired()) {
            $insights = $this->facebook->getPageInsights(
                $account->platform_account_id,
                $account->access_token
            );

            $page = $this->facebook->getPage(
                $account->platform_account_id,
                $account->access_token
            );
        } else {
            $insights = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
            $page = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
        }

        return view('facebook.metrics', compact('account', 'insights', 'page'));
    }

    /**
     * Refresh Facebook token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'facebook')
            ->findOrFail($accountId);

        if (! $account->refresh_token) {
            return redirect()->route('facebook.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->facebook->validateToken($account->refresh_token);

        if (! $result['success']) {
            return redirect()->route('facebook.index')
                ->with('error', 'Token validation failed. Please reconnect.');
        }

        $account->update([
            'token_expires_at' => $result['expires_at'] ? now()->addSeconds($result['expires_at'] - time()) : null,
        ]);

        return redirect()->route('facebook.index')
            ->with('success', 'Token validated successfully!');
    }

    /**
     * API: Publish to Facebook.
     */
    public function apiPublish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'message' => 'required|string|max:5000',
            'media_url' => 'nullable|url',
            'media_type' => 'nullable|in:photo,video',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'facebook')
            ->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        if ($account->isExpired()) {
            return response()->json(['error' => 'Token expired. Please reconnect.'], 401);
        }

        $pageId = $account->platform_account_id;
        $accessToken = $account->access_token;

        // Post with or without media
        if (! empty($validated['media_url'])) {
            $result = $validated['media_type'] === 'video'
                ? $this->facebook->postVideo($pageId, $accessToken, $validated['media_url'], $validated['message'])
                : $this->facebook->postPhoto($pageId, $accessToken, $validated['media_url'], $validated['message']);
        } else {
            $result = $this->facebook->postText($pageId, $accessToken, $validated['message']);
        }

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'post_id' => $result['post_id'],
        ]);
    }

    /**
     * API: Get Facebook insights.
     */
    public function apiInsights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'facebook')
            ->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $insights = $this->facebook->getPageInsights(
            $account->platform_account_id,
            $account->access_token
        );

        if (! $insights['success']) {
            return response()->json(['error' => $insights['error']], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $insights['data'],
        ]);
    }
}
