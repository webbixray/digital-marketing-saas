<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\InstagramApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Http\Controllers\Concerns\SocialOAuthConnectTrait;

class InstagramController extends Controller
{
    use SocialOAuthConnectTrait;

    protected string $oauthPlatform = 'instagram';
    protected string $oauthPlatformName = 'Instagram';
    protected string $oauthRoutePrefix = 'instagram';
    public function __construct(
        private readonly InstagramApiService $instagram,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show Instagram integration dashboard.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $instagramAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'instagram')
            ->active()
            ->get();

        return view('instagram.index', compact('instagramAccounts'));
    }

    /**
     * Connect an Instagram Business account via OAuth.
     */
    public function connect(Request $request): RedirectResponse
    {
        return $this->oauthConnect($request, fn ($redirectUri, $state) => $this->instagram->getAuthUrl($redirectUri, $state));
    }

    /**
     * Handle OAuth callback from Facebook/Instagram.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Verify state
        if ($request->get('state') !== $request->session()->get('instagram_state')) {
            return redirect()->route('instagram.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        // Check for errors
        if ($request->has('error')) {
            Log::error('Instagram OAuth error', [
                'error' => $request->get('error'),
                'description' => $request->get('error_description'),
            ]);

            return redirect()->route('instagram.index')
                ->with('error', 'Instagram authorization failed: '.$request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('instagram.index')
                ->with('error', 'No authorization code received.');
        }

        // Exchange code for token
        $tokenResult = $this->instagram->exchangeCodeForToken($code, route('instagram.callback'));

        if (! $tokenResult['success']) {
            Log::error('Instagram token exchange failed', $tokenResult);

            return redirect()->route('instagram.index')
                ->with('error', 'Failed to connect Instagram: '.($tokenResult['error'] ?? 'Unknown error'));
        }

        $accessToken = $tokenResult['access_token'];
        $expiresIn = $tokenResult['expires_in'] ?? null;

        // Get Facebook Pages with Instagram accounts
        $pagesResult = $this->instagram->getPages($accessToken);

        if (! $pagesResult['success'] || empty($pagesResult['pages'])) {
            return redirect()->route('instagram.index')
                ->with('error', 'No Facebook Pages found with connected Instagram Business account. Please ensure you have an Instagram Business account connected to a Facebook Page.');
        }

        // Find the first page with an Instagram account
        $page = $pagesResult['pages'][0];
        $igAccount = $page['instagram_business_account'];
        $pageAccessToken = $page['access_token'];

        // Check if account is already connected
        $existingAccount = SocialAccount::where('agency_id', $request->session()->get('instagram_connect_agency_id'))
            ->where('platform', 'instagram')
            ->where('platform_account_id', $igAccount['id'])
            ->first();

        if ($existingAccount) {
            // Update existing account tokens
            $existingAccount->update([
                'access_token' => $pageAccessToken,
                'refresh_token' => $accessToken,
                'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                'platform_username' => $igAccount['username'] ?? null,
                'platform_display_name' => $igAccount['name'] ?? $igAccount['username'] ?? null,
                'metadata' => [
                    'page_id' => $page['id'],
                    'page_name' => $page['name'],
                    'profile_picture_url' => $igAccount['profile_picture_url'] ?? null,
                    'followers_count' => $igAccount['followers_count'] ?? 0,
                    'follows_count' => $igAccount['follows_count'] ?? 0,
                    'media_count' => $igAccount['media_count'] ?? 0,
                    'biography' => $igAccount['biography'] ?? null,
                    'website' => $igAccount['website'] ?? null,
                ],
                'is_active' => true,
            ]);

            $request->session()->forget(['instagram_state', 'instagram_connect_agency_id']);

            return redirect()->route('instagram.index')
                ->with('success', 'Instagram account updated successfully!');
        }

        // Create new SocialAccount
        SocialAccount::create([
            'agency_id' => $request->session()->get('instagram_connect_agency_id'),
            'platform' => 'instagram',
            'platform_account_id' => $igAccount['id'],
            'platform_username' => $igAccount['username'] ?? null,
            'platform_display_name' => $igAccount['name'] ?? $igAccount['username'] ?? null,
            'platform_account_type' => 'business',
            'access_token' => $pageAccessToken,
            'refresh_token' => $accessToken,
            'token_type' => 'bearer',
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'metadata' => [
                'page_id' => $page['id'],
                'page_name' => $page['name'],
                'profile_picture_url' => $igAccount['profile_picture_url'] ?? null,
                'followers_count' => $igAccount['followers_count'] ?? 0,
                'follows_count' => $igAccount['follows_count'] ?? 0,
                'media_count' => $igAccount['media_count'] ?? 0,
                'biography' => $igAccount['biography'] ?? null,
                'website' => $igAccount['website'] ?? null,
            ],
            'is_active' => true,
            'is_verified' => false,
        ]);

        $request->session()->forget(['instagram_state', 'instagram_connect_agency_id']);

        return redirect()->route('instagram.index')
            ->with('success', 'Instagram account connected successfully!');
    }

    /**
     * Disconnect an Instagram account.
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
     * Get Instagram account metrics.
     */
    public function metrics(Request $request, int $accountId): View|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'instagram')
            ->findOrFail($accountId);

        if (! $account->isExpired()) {
            $insights = $this->instagram->getAccountInsights(
                $account->platform_account_id,
                $account->access_token
            );

            $profile = $this->instagram->getUserProfile(
                $account->platform_account_id,
                $account->access_token
            );
        } else {
            $insights = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
            $profile = ['success' => false, 'error' => 'Token expired. Please reconnect.'];
        }

        return view('instagram.metrics', compact('account', 'insights', 'profile'));
    }

    /**
     * Refresh Instagram token.
     */
    public function refreshToken(Request $request, int $accountId): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $account = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'instagram')
            ->findOrFail($accountId);

        if (! $account->refresh_token) {
            return redirect()->route('instagram.index')
                ->with('error', 'No refresh token available. Please reconnect.');
        }

        $result = $this->instagram->refreshToken($account->refresh_token);

        if (! $result['success']) {
            return redirect()->route('instagram.index')
                ->with('error', 'Token refresh failed: '.($result['error'] ?? 'Unknown error'));
        }

        $account->update([
            'access_token' => $result['access_token'],
            'token_expires_at' => now()->addSeconds($result['expires_in']),
        ]);

        return redirect()->route('instagram.index')
            ->with('success', 'Token refreshed successfully!');
    }

    /**
     * Publish content to Instagram via API.
     */
    public function apiPublish(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'account_id' => 'required|integer|exists:social_accounts,id',
                'caption' => 'required|string|max:2200',
                'media_url' => 'required|url',
                'media_type' => 'required|in:image,video,carousel',
            ]);

            $agencyId = $request->user()->agency_id;
            $account = SocialAccount::where('agency_id', $agencyId)
                ->where('platform', 'instagram')
                ->findOrFail($request->account_id);

            $result = $this->instagram->publishMedia(
                $account->platform_account_id,
                $account->access_token,
                $request->media_url,
                $request->caption,
                $request->media_type
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Instagram API publish failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to publish content'], 500);
        }
    }

    /**
     * Get Instagram insights via API.
     */
    public function apiInsights(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'account_id' => 'required|integer|exists:social_accounts,id',
                'metrics' => 'nullable|array',
                'period' => 'nullable|in:day,lifetime',
            ]);

            $agencyId = $request->user()->agency_id;
            $account = SocialAccount::where('agency_id', $agencyId)
                ->where('platform', 'instagram')
                ->findOrFail($request->account_id);

            $insights = $this->instagram->getAccountInsights(
                $account->platform_account_id,
                $account->access_token,
                $request->metrics,
                $request->period
            );

            return response()->json($insights);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Instagram API insights failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch insights'], 500);
        }
    }
}
