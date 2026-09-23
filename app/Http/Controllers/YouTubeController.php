<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\YouTubeApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\SocialOAuthConnectTrait;

class YouTubeController extends Controller
{
    use SocialOAuthConnectTrait;

    protected string $oauthPlatform = 'youtube';
    protected string $oauthPlatformName = 'YouTube';
    protected string $oauthRoutePrefix = 'youtube';
    public function __construct(
        private readonly YouTubeApiService $youtube,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show YouTube integration dashboard.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $youtubeAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'youtube')
            ->active()
            ->get();

        return view('youtube.index', compact('youtubeAccounts'));
    }

    /**
     * Connect a YouTube channel via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        return $this->oauthConnect($request, fn ($redirectUri, $state) => $this->youtube->getAuthUrl($redirectUri, $state));
    }

    /**
     * Handle OAuth callback from Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== $request->session()->get('youtube_state')) {
            return redirect()->route('youtube.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            Log::error('YouTube OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);

            return redirect()->route('youtube.index')
                ->with('error', 'YouTube authorization failed: '.$request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('youtube.index')
                ->with('error', 'No authorization code received.');
        }

        $tokenResult = $this->youtube->exchangeCodeForToken($code, route('youtube.callback'));

        if (! $tokenResult['success']) {
            Log::error('YouTube token exchange failed', $tokenResult);

            return redirect()->route('youtube.index')
                ->with('error', 'Failed to connect YouTube: '.($tokenResult['error'] ?? 'Unknown error'));
        }

        $accessToken = $tokenResult['access_token'];
        $expiresIn = $tokenResult['expires_in'] ?? null;

        $channelResult = $this->youtube->getMyChannel($accessToken);

        if (! $channelResult['success']) {
            return redirect()->route('youtube.index')
                ->with('error', 'Failed to fetch YouTube channel.');
        }

        $channel = $channelResult['data'];
        $agencyId = $request->session()->get('youtube_connect_agency_id');

        // Check if account is already connected
        $existingAccount = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'youtube')
            ->where('platform_account_id', $channel['id'])
            ->first();

        $accountData = [
            'platform' => 'youtube',
            'platform_account_id' => $channel['id'],
            'platform_username' => $channel['title'] ?? null,
            'platform_display_name' => $channel['title'] ?? null,
            'platform_account_type' => 'channel',
            'access_token' => $accessToken,
            'refresh_token' => $tokenResult['refresh_token'] ?? null,
            'token_type' => 'bearer',
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'metadata' => [
                'description' => $channel['description'] ?? null,
                'custom_url' => $channel['custom_url'] ?? null,
                'thumbnail' => $channel['thumbnail'] ?? null,
                'subscriber_count' => $channel['subscriber_count'] ?? 0,
                'video_count' => $channel['video_count'] ?? 0,
                'view_count' => $channel['view_count'] ?? 0,
                'comment_count' => $channel['comment_count'] ?? 0,
                'published_at' => $channel['published_at'] ?? null,
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

        $request->session()->forget(['youtube_state', 'youtube_connect_agency_id']);

        return redirect()->route('youtube.index')
            ->with('success', 'YouTube channel connected successfully!');
    }

    /**
     * Disconnect a YouTube channel.
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
     * Get YouTube account metrics.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'youtube')
            ->findOrFail($accountId);

        if (! $account->isExpired()) {
            $channelStats = $this->youtube->getChannelStats($account->access_token, $account->platform_account_id);
            $videos = $this->youtube->getMyVideos($account->access_token, 25);
        } else {
            $channelStats = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
            $videos = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
        }

        return view('youtube.metrics', compact('account', 'channelStats', 'videos'));
    }

    /**
     * Refresh YouTube token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'youtube')
            ->findOrFail($accountId);

        if (! $account->refresh_token) {
            return redirect()->route('youtube.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->youtube->refreshToken($account->refresh_token);

        if (! $result['success']) {
            return redirect()->route('youtube.index')
                ->with('error', 'Token refresh failed: '.($result['error'] ?? 'Unknown error'));
        }

        $account->update([
            'access_token' => $result['access_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in']),
        ]);

        return redirect()->route('youtube.index')
            ->with('success', 'Token refreshed successfully!');
    }

    /**
     * API: Upload video to YouTube.
     */
    public function apiUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'title' => 'required|string|max=100',
            'description' => 'nullable|string|max=5000',
            'video' => 'required|file|mimetypes:video/*',
            'privacy' => 'nullable|in:private,public,unlisted',
        ]);

        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('id', $validated['account_id'])
            ->where('platform', 'youtube')
            ->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        if ($account->isExpired()) {
            return response()->json(['error' => 'Token expired. Please reconnect.'], 401);
        }

        $result = $this->youtube->uploadVideo(
            $account->access_token,
            $validated['video'],
            $validated['title'],
            $validated['description'] ?? '',
            ['privacy' => $validated['privacy'] ?? 'private']
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'video_id' => $result['video_id'],
            'url' => $result['url'],
        ]);
    }
}
