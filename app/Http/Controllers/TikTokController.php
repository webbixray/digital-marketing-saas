<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\TikTokApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\SocialOAuthConnectTrait;

class TikTokController extends Controller
{
    use SocialOAuthConnectTrait;

    protected string $oauthPlatform = 'tiktok';
    protected string $oauthPlatformName = 'TikTok';
    protected string $oauthRoutePrefix = 'tiktok';
    public function __construct(
        private readonly TikTokApiService $tiktok,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show TikTok integration dashboard.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $tiktokAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'tiktok')
            ->active()
            ->get();

        return view('tiktok.index', compact('tiktokAccounts'));
    }

    /**
     * Connect a TikTok account via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        return $this->oauthConnect($request, fn ($redirectUri, $state) => $this->tiktok->getAuthUrl($redirectUri, $state));
    }

    /**
     * Handle OAuth callback from TikTok.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== $request->session()->get('tiktok_state')) {
            return redirect()->route('tiktok.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            Log::error('TikTok OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);

            return redirect()->route('tiktok.index')
                ->with('error', 'TikTok authorization failed: '.$request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('tiktok.index')
                ->with('error', 'No authorization code received.');
        }

        $tokenResult = $this->tiktok->exchangeCodeForToken($code, route('tiktok.callback'));

        if (! $tokenResult['success']) {
            Log::error('TikTok token exchange failed', $tokenResult);

            return redirect()->route('tiktok.index')
                ->with('error', 'Failed to connect TikTok: '.($tokenResult['error'] ?? 'Unknown error'));
        }

        $accessToken = $tokenResult['access_token'];
        $expiresIn = $tokenResult['expires_in'] ?? null;

        // Get user info
        $userInfo = $this->tiktok->getUserInfo($accessToken);

        if (! $userInfo['success']) {
            return redirect()->route('tiktok.index')
                ->with('error', 'Failed to fetch TikTok profile.');
        }

        $user = $userInfo['data'];
        $agencyId = $request->session()->get('tiktok_connect_agency_id');

        // Check if account is already connected
        $existingAccount = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'tiktok')
            ->where('platform_account_id', $user['open_id'] ?? '')
            ->first();

        $accountData = [
            'platform' => 'tiktok',
            'platform_account_id' => $user['open_id'] ?? null,
            'platform_username' => $user['username'] ?? null,
            'platform_display_name' => $user['display_name'] ?? null,
            'platform_account_type' => 'creator',
            'access_token' => $accessToken,
            'refresh_token' => $tokenResult['refresh_token'] ?? null,
            'token_type' => 'bearer',
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'metadata' => [
                'avatar_url' => $user['avatar_url'] ?? null,
                'bio' => $user['bio_description'] ?? null,
                'follower_count' => $user['follower_count'] ?? 0,
                'following_count' => $user['following_count'] ?? 0,
                'likes_count' => $user['likes_count'] ?? 0,
                'video_count' => $user['video_count'] ?? 0,
                'union_id' => $user['union_id'] ?? null,
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

        $request->session()->forget(['tiktok_state', 'tiktok_connect_agency_id']);

        return redirect()->route('tiktok.index')
            ->with('success', 'TikTok account connected successfully!');
    }

    /**
     * Disconnect a TikTok account.
     */
    public function disconnect(Request $request, int $accountId): RedirectResponse
    {
        return $this->oauthDisconnect($request, $accountId);
    }

    /**
     * Toggle account active status.
     */
    public function toggle(Request $request, int $accountId): RedirectResponse
    {
        return $this->oauthToggle($request, $accountId);
    }

    /**
     * Get TikTok account metrics.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'tiktok')
            ->findOrFail($accountId);

        if (! $account->isExpired()) {
            $profile = $this->tiktok->getUserInfo($account->access_token);
        } else {
            $profile = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
        }

        return view('tiktok.metrics', compact('account', 'profile'));
    }

    /**
     * Refresh TikTok token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'tiktok')
            ->findOrFail($accountId);

        if (! $account->refresh_token) {
            return redirect()->route('tiktok.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->tiktok->refreshToken($account->refresh_token);

        if (! $result['success']) {
            return redirect()->route('tiktok.index')
                ->with('error', 'Token refresh failed: '.($result['error'] ?? 'Unknown error'));
        }

        $account->update([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in']),
        ]);

        return redirect()->route('tiktok.index')
            ->with('success', 'Token refreshed successfully!');
    }

    /**
     * API: Publish video to TikTok.
     */
    public function apiPublish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'video_url' => 'required|url',
            'title' => 'required|string|max=150',
            'description' => 'nullable|string|max=4000',
            'allow_comment' => 'nullable|boolean',
            'allow_duet' => 'nullable|boolean',
            'allow_stitch' => 'nullable|boolean',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'tiktok')
            ->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        if ($account->isExpired()) {
            return response()->json(['error' => 'Token expired. Please reconnect.'], 401);
        }

        $options = [
            'allow_comment' => $validated['allow_comment'] ?? true,
            'allow_duet' => $validated['allow_duet'] ?? true,
            'allow_stitch' => $validated['allow_stitch'] ?? true,
        ];

        $result = $this->tiktok->publishVideo(
            $account->access_token,
            $validated['video_url'],
            $validated['title'],
            $validated['description'] ?? null,
            $options
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'publish_id' => $result['publish_id'],
        ]);
    }

    /**
     * API: Get TikTok videos.
     */
    public function apiVideos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'cursor' => 'nullable|integer|min:0',
            'max_count' => 'nullable|integer|min:1|max=20',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'tiktok')
            ->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $result = $this->tiktok->getVideos(
            $account->access_token,
            $validated['cursor'] ?? 0,
            $validated['max_count'] ?? 20
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
