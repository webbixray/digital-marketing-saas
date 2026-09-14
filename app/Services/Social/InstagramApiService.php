<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramApiService
{
    protected ?string $appId;

    protected ?string $appSecret;

    protected string $baseUrl = 'https://graph.facebook.com/v21.0';

    public function __construct()
    {
        $this->appId = config('services.instagram.client_id', env('INSTAGRAM_CLIENT_ID', ''));
        $this->appSecret = config('services.instagram.client_secret', env('INSTAGRAM_CLIENT_SECRET', ''));
    }

    /**
     * Get the Facebook authorization URL for Instagram Graph API.
     * User must have a Facebook Page connected to an Instagram Business account.
     */
    public function getAuthUrl(string $redirectUri, string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'scope' => 'pages_read_engagement,instagram_basic,instagram_content_publish,instagram_manage_insights',
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://www.facebook.com/v21.0/dialog/oauth?{$params}";
    }

    /**
     * Exchange authorization code for a long-lived access token.
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        try {
            // Step 1: Exchange for short-lived token
            $shortLived = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/access_token", [
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (! $shortLived->successful()) {
                Log::error('Instagram short-lived token exchange failed', [
                    'response' => $shortLived->json(),
                ]);

                return ['success' => false, 'error' => 'Token exchange failed'];
            }

            $shortToken = $shortLived->json()['access_token'] ?? null;
            if (! $shortToken) {
                return ['success' => false, 'error' => 'No access token returned'];
            }

            // Step 2: Exchange for long-lived token
            $longLived = Http::timeout(30)
                ->get("{$this->baseUrl}/oauth/access_token", [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'fb_exchange_token' => $shortToken,
                ]);

            if (! $longLived->successful()) {
                Log::error('Instagram long-lived token exchange failed', [
                    'response' => $longLived->json(),
                ]);

                // Fall back to short-lived token
                return [
                    'success' => true,
                    'access_token' => $shortToken,
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                    'long_lived' => false,
                ];
            }

            $data = $longLived->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 5184000, // ~60 days
                'long_lived' => true,
            ];
        } catch (\Exception $e) {
            Log::error('Instagram token exchange failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Facebook Pages the user manages (needed to find Instagram accounts).
     */
    public function getPages(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/me/accounts", [
                    'fields' => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url,followers_count,follows_count,media_count}',
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram getPages failed', ['response' => $response->json()]);

                return ['success' => false, 'error' => 'Failed to fetch pages'];
            }

            $pages = $response->json()['data'] ?? [];

            // Filter only pages with connected Instagram accounts
            $pagesWithIg = array_filter($pages, fn ($p) => isset($p['instagram_business_account']));
            $pagesWithIg = array_values($pagesWithIg);

            return [
                'success' => true,
                'pages' => $pagesWithIg,
            ];
        } catch (\Exception $e) {
            Log::error('Instagram getPages failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Instagram account details for a given page access token.
     */
    public function getInstagramAccount(string $pageAccessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/me/accounts", [
                    'fields' => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url,name,followers_count,follows_count,media_count,biography,website}',
                    'access_token' => $pageAccessToken,
                ]);

            if (! $response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch Instagram account'];
            }

            $pages = $response->json()['data'] ?? [];
            foreach ($pages as $page) {
                if (isset($page['instagram_business_account'])) {
                    return [
                        'success' => true,
                        'page' => [
                            'id' => $page['id'],
                            'name' => $page['name'],
                            'access_token' => $page['access_token'],
                        ],
                        'instagram' => $page['instagram_business_account'],
                    ];
                }
            }

            return ['success' => false, 'error' => 'No Instagram Business account found. Please connect an Instagram Business account to your Facebook Page.'];
        } catch (\Exception $e) {
            Log::error('Instagram getInstagramAccount failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Instagram user profile and metrics.
     */
    public function getUserProfile(string $igUserId, string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$igUserId}", [
                    'fields' => 'id,username,name,profile_picture_url,biography,followers_count,follows_count,media_count,website',
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram getUserProfile failed', ['response' => $response->json()]);

                return ['success' => false, 'error' => 'Failed to fetch user profile'];
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Instagram getUserProfile failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Instagram account insights (requires Instagram Business account).
     */
    public function getAccountInsights(string $igUserId, string $accessToken, string $period = 'day'): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$igUserId}/insights", [
                    'metric' => 'impressions,reach,profile_views,follower_count,website_clicks',
                    'period' => $period,
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram getAccountInsights failed', ['response' => $response->json()]);

                return ['success' => false, 'error' => 'Failed to fetch insights'];
            }

            return [
                'success' => true,
                'data' => $response->json()['data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Instagram getAccountInsights failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a media container for Instagram (required before publishing).
     */
    public function createMediaContainer(string $igUserId, string $accessToken, array $params): array
    {
        try {
            $payload = [
                'access_token' => $accessToken,
            ];

            // Single image
            if (isset($params['image_url'])) {
                $payload['image_url'] = $params['image_url'];
                if (isset($params['caption'])) {
                    $payload['caption'] = $params['caption'];
                }
            }
            // Reels
            elseif (isset($params['video_url']) && ($params['is_reel'] ?? false)) {
                $payload['video_url'] = $params['video_url'];
                $payload['media_type'] = 'REELS';
                if (isset($params['caption'])) {
                    $payload['caption'] = $params['caption'];
                }
            }
            // Carousel
            elseif (isset($params['children'])) {
                // children is a comma-separated list of media container IDs
                $payload['media_type'] = 'CAROUSEL';
                $payload['children'] = $params['children'];
                if (isset($params['caption'])) {
                    $payload['caption'] = $params['caption'];
                }
            } else {
                return ['success' => false, 'error' => 'Invalid media parameters. Provide image_url, video_url, or children.'];
            }

            // Optional cover image for reels
            if (isset($params['cover_url'])) {
                $payload['cover_url'] = $params['cover_url'];
            }

            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/{$igUserId}/media", $payload);

            if (! $response->successful()) {
                Log::error('Instagram createMediaContainer failed', [
                    'response' => $response->json(),
                ]);

                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to create media container'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'container_id' => $data['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Instagram createMediaContainer failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish a media container to Instagram.
     */
    public function publishMedia(string $igUserId, string $accessToken, string $containerId): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/{$igUserId}/media_publish", [
                    'creation_id' => $containerId,
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram publishMedia failed', [
                    'response' => $response->json(),
                ]);

                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to publish media'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'media_id' => $data['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Instagram publishMedia failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get media insights for a specific post.
     */
    public function getMediaInsights(string $mediaId, string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$mediaId}/insights", [
                    'metric' => 'engagement,impressions,reach,saved,comments,likes,shares',
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram getMediaInsights failed', ['response' => $response->json()]);

                return ['success' => false, 'error' => 'Failed to fetch media insights'];
            }

            $insights = [];
            foreach ($response->json()['data'] ?? [] as $item) {
                $insights[$item['name']] = [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'values' => array_sum(array_column($item['values'] ?? [], 'value')),
                ];
            }

            return [
                'success' => true,
                'data' => $insights,
            ];
        } catch (\Exception $e) {
            Log::error('Instagram getMediaInsights failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post to Instagram (full flow: create container -> publish).
     */
    public function post(string $igUserId, string $accessToken, array $params): array
    {
        // Step 1: Create media container
        $container = $this->createMediaContainer($igUserId, $accessToken, $params);
        if (! $container['success']) {
            return $container;
        }

        // Step 2: Wait for media processing (reels need time)
        if (isset($params['video_url'])) {
            $maxAttempts = 10;
            $attempt = 0;
            while ($attempt < $maxAttempts) {
                sleep(3);
                $status = $this->getContainerStatus($container['container_id'], $accessToken);
                if (($status['status_code'] ?? '') === 'FINISHED') {
                    break;
                }
                if (($status['status_code'] ?? '') === 'ERROR') {
                    return ['success' => false, 'error' => 'Media processing failed: '.($status['error'] ?? 'Unknown error')];
                }
                $attempt++;
            }
        }

        // Step 3: Publish the container
        return $this->publishMedia($igUserId, $accessToken, $container['container_id']);
    }

    /**
     * Check media container status.
     */
    public function getContainerStatus(string $containerId, string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$containerId}", [
                    'fields' => 'status_code,status',
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                return ['success' => false, 'error' => 'Failed to check container status'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'status_code' => $data['status_code'] ?? 'UNKNOWN',
                'status' => $data['status'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            Log::error('Instagram getContainerStatus failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a published Instagram media.
     */
    public function deleteMedia(string $mediaId, string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->delete("{$this->baseUrl}/{$mediaId}", [
                    'access_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                Log::error('Instagram deleteMedia failed', ['response' => $response->json()]);

                return ['success' => false, 'error' => 'Failed to delete media'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Instagram deleteMedia failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refresh a long-lived token before it expires.
     */
    public function refreshToken(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/oauth/access_token", [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'fb_exchange_token' => $accessToken,
                ]);

            if (! $response->successful()) {
                return ['success' => false, 'error' => 'Token refresh failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'] ?? 5184000,
            ];
        } catch (\Exception $e) {
            Log::error('Instagram refreshToken failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate access token and check permissions.
     */
    public function validateToken(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/debug_token", [
                    'input_token' => $accessToken,
                    'access_token' => "{$this->appId}|{$this->appSecret}",
                ]);

            if (! $response->successful()) {
                return ['success' => false, 'error' => 'Token validation failed'];
            }

            $data = $response->json()['data'] ?? [];

            if (isset($data['error'])) {
                return ['success' => false, 'error' => $data['error']['message']];
            }

            if (! ($data['is_valid'] ?? false)) {
                return ['success' => false, 'error' => 'Token is invalid'];
            }

            return [
                'success' => true,
                'app_id' => $data['app_id'],
                'expires_at' => $data['expires_at'] ?? null,
                'scopes' => $data['scopes'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Instagram validateToken failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
