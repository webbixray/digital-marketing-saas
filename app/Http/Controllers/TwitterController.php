<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Social\TwitterApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwitterController extends Controller
{
    public function __construct(
        private readonly TwitterApiService $twitter,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show Twitter integration dashboard.
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $agencyId = $request->user()->agency_id;
        
        $twitterAccounts = SocialAccount::where('agency_id', $agencyId)
            ->where('platform', 'twitter')
            ->get();
        
        return view('twitter.index', compact('twitterAccounts'));
    }

    /**
     * Connect a Twitter account via OAuth 2.0.
     */
    public function connect(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;
        
        // Generate PKCE code verifier and challenge
        $codeVerifier = Str::random(128);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');
        
        // Store code verifier in session
        $request->session()->put('twitter_code_verifier', $codeVerifier);
        $request->session()->put('twitter_state', $state = Str::random(32));
        
        // Build authorization URL
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.twitter.client_id', env('TWITTER_CLIENT_ID')),
            'redirect_uri' => route('twitter.callback'),
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
        
        return redirect('https://twitter.com/i/oauth2/authorize?' . $params);
    }

    /**
     * Handle OAuth callback from Twitter.
     */
    public function callback(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;
        
        // Verify state
        if ($request->get('state') !== $request->session()->get('twitter_state')) {
            return redirect()->route('social.accounts.index')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }
        
        $code = $request->get('code');
        $codeVerifier = $request->session()->get('twitter_code_verifier');
        
        if (!$code || !$codeVerifier) {
            return redirect()->route('social.accounts.index')
                ->with('error', 'Authorization failed. Please try again.');
        }
        
        try {
            // Exchange code for access token
            $response = Http::asForm()->withBasicAuth(
                config('services.twitter.client_id', env('TWITTER_CLIENT_ID')),
                config('services.twitter.client_secret', env('TWITTER_CLIENT_SECRET'))
            )->post('https://api.twitter.com/2/oauth2/token', [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'client_id' => config('services.twitter.client_id', env('TWITTER_CLIENT_ID')),
                'redirect_uri' => route('twitter.callback'),
                'code_verifier' => $codeVerifier,
            ]);
            
            if ($response->failed()) {
                Log::error('Twitter OAuth token exchange failed: ' . $response->body());
                return redirect()->route('social.accounts.index')
                    ->with('error', 'Failed to exchange authorization code.');
            }
            
            $tokenData = $response->json();
            
            // Get user info
            $userResponse = Http::withToken($tokenData['access_token'])
                ->get('https://api.twitter.com/2/users/me', [
                    'user.fields' => 'id,name,username,profile_image_url,public_metrics',
                ]);
            
            if ($userResponse->failed()) {
                return redirect()->route('social.accounts.index')
                    ->with('error', 'Failed to fetch user information.');
            }
            
            $userData = $userResponse->json('data');
            
            // Create or update social account
            SocialAccount::updateOrCreate(
                [
                    'agency_id' => $agencyId,
                    'platform' => 'twitter',
                    'platform_account_id' => $userData['id'],
                ],
                [
                    'platform_username' => $userData['username'],
                    'platform_display_name' => $userData['name'],
                    'platform_account_type' => 'personal',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in']) 
                        ? now()->addSeconds($tokenData['expires_in']) 
                        : null,
                    'token_type' => $tokenData['token_type'] ?? 'bearer',
                    'scope' => $tokenData['scope'] ?? '',
                    'metadata' => [
                        'profile_image_url' => $userData['profile_image_url'] ?? null,
                        'public_metrics' => $userData['public_metrics'] ?? [],
                    ],
                    'is_active' => true,
                    'is_verified' => true,
                ]
            );
            
            return redirect()->route('social.accounts.index')
                ->with('success', 'Twitter account connected successfully!');
            
        } catch (\Exception $e) {
            Log::error('Twitter OAuth callback failed: ' . $e->getMessage());
            return redirect()->route('social.accounts.index')
                ->with('error', 'An error occurred during authorization.');
        }
    }

    /**
     * Disconnect a Twitter account.
     */
    public function disconnect(Request $request, int $accountId): JsonResponse|RedirectResponse
    {
        $agencyId = $request->user()->agency_id;
        
        $account = SocialAccount::where('id', $accountId)
            ->where('agency_id', $agencyId)
            ->where('platform', 'twitter')
            ->first();
        
        if (!$account) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Account not found'], 404)
                : redirect()->route('social.accounts.index')->with('error', 'Account not found');
        }
        
        $account->delete();
        
        return $request->expectsJson()
            ? response()->json(['message' => 'Twitter account disconnected'])
            : redirect()->route('social.accounts.index')->with('success', 'Twitter account disconnected');
    }

    /**
     * Get Twitter account metrics.
     */
    public function metrics(Request $request, int $accountId): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        
        $account = SocialAccount::where('id', $accountId)
            ->where('agency_id', $agencyId)
            ->where('platform', 'twitter')
            ->firstOrFail();
        
        $twitter = $this->twitter;
        
        // Use account's access token for user-context requests
        $metrics = $twitter->getUserMetrics($account->platform_username);
        
        return response()->json($metrics);
    }

    /**
     * Post a tweet.
     */
    public function postTweet(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'account_id' => 'required|integer|exists:social_accounts,id',
            'text' => 'required|string|max:280',
        ]);
        
        $agencyId = $request->user()->agency_id;
        
        $account = SocialAccount::where('id', $request->account_id)
            ->where('agency_id', $agencyId)
            ->where('platform', 'twitter')
            ->firstOrFail();
        
        $twitter = $this->twitter;
        $result = $twitter->postTweet($request->text);
        
        if ($result['success']) {
            // Log the post
            \App\Models\SocialPost::create([
                'agency_id' => $agencyId,
                'social_account_id' => $account->id,
                'platform' => 'twitter',
                'content' => $request->text,
                'platform_post_id' => $result['tweet_id'],
                'status' => 'published',
                'published_at' => now(),
            ]);
            
            return $request->expectsJson()
                ? response()->json(['message' => 'Tweet posted successfully', 'data' => $result['data']])
                : back()->with('success', 'Tweet posted successfully!');
        }
        
        return $request->expectsJson()
            ? response()->json(['error' => $result['error']], 422)
            : back()->with('error', $result['error']);
    }

    /**
     * Get user's Twitter timeline.
     */
    public function timeline(Request $request, int $accountId): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        
        $account = SocialAccount::where('id', $accountId)
            ->where('agency_id', $agencyId)
            ->where('platform', 'twitter')
            ->firstOrFail();
        
        try {
            $response = Http::withToken($account->access_token)
                ->get("https://api.twitter.com/2/users/{$account->platform_account_id}/tweets", [
                    'max_results' => 20,
                    'tweet.fields' => 'created_at,public_metrics',
                ]);
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
