<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwitterApiService
{
    protected ?string $apiKey;

    protected ?string $apiSecret;

    protected ?string $accessToken;

    protected ?string $accessSecret;

    protected ?string $bearerToken;

    protected string $baseUrl = 'https://api.twitter.com';

    public function __construct()
    {
        $this->apiKey = config('services.twitter.api_key', env('TWITTER_API_KEY', ''));
        $this->apiSecret = config('services.twitter.api_secret', env('TWITTER_API_SECRET', ''));
        $this->accessToken = config('services.twitter.access_token', env('TWITTER_ACCESS_TOKEN', ''));
        $this->accessSecret = config('services.twitter.access_secret', env('TWITTER_ACCESS_SECRET', ''));
        $this->bearerToken = config('services.twitter.bearer_token', env('TWITTER_BEARER_TOKEN', ''));
    }

    /**
     * Authenticate with Twitter API v2 by verifying credentials.
     * Returns user info if authentication is successful.
     */
    public function authenticate(): array
    {
        try {
            $url = "{$this->baseUrl}/2/users/me";
            $response = Http::withToken($this->bearerToken)
                ->timeout(30)
                ->get($url, ['user.fields' => 'id,name,username,public_metrics']);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Authentication failed',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter authentication failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post a tweet to Twitter/X with optional media.
     * Uses OAuth 1.0a for user-context requests.
     */
    public function postTweet(string $text, array $media = []): array
    {
        try {
            $url = "{$this->baseUrl}/2/tweets";

            $payload = ['text' => $text];

            // If media IDs are provided, attach them
            if (! empty($media)) {
                $payload['media'] = [
                    'media_ids' => $media,
                ];
            }

            $oauthHeaders = $this->buildOAuth1Headers('POST', $url);

            $response = Http::withHeaders($oauthHeaders)
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'tweet_id' => $response->json()['data']['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Tweet posting failed',
                'errors' => $response->json()['errors'] ?? [],
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter postTweet failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user metrics for a given Twitter username.
     */
    public function getUserMetrics(string $username): array
    {
        try {
            $url = "{$this->baseUrl}/2/users/by/username/{$username}";

            $response = Http::withToken($this->bearerToken)
                ->timeout(30)
                ->get($url, [
                    'user.fields' => 'public_metrics,description,profile_image_url,verified,created_at',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $metrics = $data['data']['public_metrics'] ?? [];

                return [
                    'success' => true,
                    'data' => [
                        'id' => $data['data']['id'] ?? null,
                        'username' => $data['data']['username'] ?? $username,
                        'name' => $data['data']['name'] ?? null,
                        'followers_count' => $metrics['followers_count'] ?? 0,
                        'following_count' => $metrics['following_count'] ?? 0,
                        'tweet_count' => $metrics['tweet_count'] ?? 0,
                        'listed_count' => $metrics['listed_count'] ?? 0,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Failed to get user metrics',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter getUserMetrics failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get metrics for a specific tweet.
     */
    public function getTweetMetrics(string $tweetId): array
    {
        try {
            $url = "{$this->baseUrl}/2/tweets/{$tweetId}";

            $response = Http::withToken($this->bearerToken)
                ->timeout(30)
                ->get($url, [
                    'tweet.fields' => 'public_metrics,created_at,lang,source',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $metrics = $data['data']['public_metrics'] ?? [];

                return [
                    'success' => true,
                    'data' => [
                        'id' => $data['data']['id'] ?? $tweetId,
                        'text' => $data['data']['text'] ?? '',
                        'retweet_count' => $metrics['retweet_count'] ?? 0,
                        'reply_count' => $metrics['reply_count'] ?? 0,
                        'like_count' => $metrics['like_count'] ?? 0,
                        'quote_count' => $metrics['quote_count'] ?? 0,
                        'impression_count' => $metrics['impression_count'] ?? 0,
                        'created_at' => $data['data']['created_at'] ?? null,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Failed to get tweet metrics',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter getTweetMetrics failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build OAuth 1.0a headers for a request.
     */
    protected function buildOAuth1Headers(string $method, string $url): array
    {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => Str::random(32),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        // Build signature base string
        $baseString = $this->buildBaseString($method, $url, $oauth);
        $signingKey = rawurlencode($this->apiSecret).'&'.rawurlencode($this->accessSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Build Authorization header
        $headerParts = [];
        foreach ($oauth as $key => $value) {
            $headerParts[] = rawurlencode($key).'="'.rawurlencode($value).'"';
        }

        return [
            'Authorization' => 'OAuth '.implode(', ', $headerParts),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Build the OAuth 1.0a signature base string.
     */
    protected function buildBaseString(string $method, string $url, array $params): string
    {
        $parts = [];
        ksort($params);
        foreach ($params as $key => $value) {
            $parts[] = rawurlencode($key).'='.rawurlencode($value);
        }

        return $method.'&'.rawurlencode($url).'&'.rawurlencode(implode('&', $parts));
    }

    /**
     * Check if API credentials are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey)
            && ! empty($this->apiSecret)
            && ! empty($this->accessToken)
            && ! empty($this->accessSecret);
    }

    /**
     * Check if Bearer token is configured.
     */
    public function hasBearerToken(): bool
    {
        return ! empty($this->bearerToken);
    }
}
