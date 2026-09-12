<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookApiService
{
    protected ?string $appId;
    protected ?string $appSecret;
    protected string $baseUrl = 'https://graph.facebook.com/v21.0';

    public function __construct()
    {
        $this->appId = config('services.facebook.client_id', env('FACEBOOK_CLIENT_ID', ''));
        $this->appSecret = config('services.facebook.client_secret', env('FACEBOOK_CLIENT_SECRET', ''));
    }

    /**
     * Get Facebook Login authorization URL.
     */
    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        $defaultScopes = ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list', 'public_profile'];
        $allScopes = array_unique(array_merge($defaultScopes, $scopes));

        $params = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $allScopes),
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
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/access_token", [
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook token exchange failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token exchange failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'expires_in' => $data['expires_in'] ?? 5184000,
            ];
        } catch (\Exception $e) {
            Log::error('Facebook token exchange failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Facebook Pages the user manages.
     */
    public function getPages(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/me/accounts", [
                    'fields' => 'id,name,access_token,category,fan_count,link,picture{url},about,website',
                    'access_token' => $accessToken,
                    'limit' => 100,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook getPages failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch pages'];
            }

            return [
                'success' => true,
                'pages' => $response->json()['data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Facebook getPages failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Facebook Page details.
     */
    public function getPage(string $pageId, string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$pageId}", [
                    'fields' => 'id,name,about,category,fan_count,link,picture{url},website,location,phone,description',
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch page details'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Facebook getPage failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Page insights (fans, impressions, reach).
     */
    public function getPageInsights(string $pageId, string $accessToken, string $period = 'day', int $days = 30): array
    {
        try {
            $metrics = 'page_impressions,page_impressions_unique,page_fans,page_fan_adds,page_fan_removes,page_engaged_users,page_posts_impressions';

            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$pageId}/insights", [
                    'metric' => $metrics,
                    'period' => $period,
                    'since' => now()->subDays($days)->timestamp,
                    'until' => now()->timestamp,
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook getPageInsights failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch insights'];
            }

            $insights = [];
            foreach ($response->json()['data'] ?? [] as $item) {
                $values = array_column($item['values'] ?? [], 'value');
                $insights[$item['name']] = [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'values' => array_sum($values),
                ];
            }

            return ['success' => true, 'data' => $insights];
        } catch (\Exception $e) {
            Log::error('Facebook getPageInsights failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post text to Facebook Page.
     */
    public function postText(string $pageId, string $accessToken, string $message): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/{$pageId}/feed", [
                    'message' => $message,
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook postText failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to post'];
            }

            return [
                'success' => true,
                'post_id' => $response->json()['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Facebook postText failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post photo to Facebook Page.
     */
    public function postPhoto(string $pageId, string $accessToken, string $imageUrl, ?string $message = null): array
    {
        try {
            $payload = [
                'url' => $imageUrl,
                'access_token' => $accessToken,
            ];

            if ($message) {
                $payload['caption'] = $message;
            }

            $response = Http::asForm()
                ->timeout(60)
                ->post("{$this->baseUrl}/{$pageId}/photos", $payload);

            if (!$response->successful()) {
                Log::error('Facebook postPhoto failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to post photo'];
            }

            return [
                'success' => true,
                'post_id' => $response->json()['id'],
                'photo_id' => $response->json()['post_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Facebook postPhoto failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post video to Facebook Page.
     */
    public function postVideo(string $pageId, string $accessToken, string $videoUrl, ?string $description = null): array
    {
        try {
            $payload = [
                'file_url' => $videoUrl,
                'access_token' => $accessToken,
            ];

            if ($description) {
                $payload['description'] = $description;
            }

            $response = Http::asForm()
                ->timeout(120)
                ->post("{$this->baseUrl}/{$pageId}/videos", $payload);

            if (!$response->successful()) {
                Log::error('Facebook postVideo failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to post video'];
            }

            return [
                'success' => true,
                'video_id' => $response->json()['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Facebook postVideo failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get post insights (likes, comments, shares, reach).
     */
    public function getPostInsights(string $postId, string $accessToken): array
    {
        try {
            $metrics = 'post_impressions,post_impressions_unique,post_clicks,post_engaged_users,post_reactions_by_type_total,post_comments,post_shares';

            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$postId}/insights", [
                    'metric' => $metrics,
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook getPostInsights failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch post insights'];
            }

            $insights = [];
            foreach ($response->json()['data'] ?? [] as $item) {
                $values = array_column($item['values'] ?? [], 'value');
                $insights[$item['name']] = [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'values' => is_array($values[0] ?? null) ? array_sum($values[0]) : ($values[0] ?? 0),
                ];
            }

            return ['success' => true, 'data' => $insights];
        } catch (\Exception $e) {
            Log::error('Facebook getPostInsights failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Facebook post.
     */
    public function deletePost(string $postId, string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->delete("{$this->baseUrl}/{$postId}", [
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook deletePost failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to delete post'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Facebook deletePost failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get recent posts from Facebook Page.
     */
    public function getPosts(string $pageId, string $accessToken, int $limit = 25): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/{$pageId}/posts", [
                    'fields' => 'id,message,created_time,full_picture,permalink_url,likes.summary(true),comments.summary(true),shares',
                    'limit' => $limit,
                    'access_token' => $accessToken,
                ]);

            if (!$response->successful()) {
                Log::error('Facebook getPosts failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch posts'];
            }

            return [
                'success' => true,
                'posts' => $response->json()['data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Facebook getPosts failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate access token.
     */
    public function validateToken(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)
                ->get("{$this->baseUrl}/debug_token", [
                    'input_token' => $accessToken,
                    'access_token' => "{$this->appId}|{$this->appSecret}",
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Token validation failed'];
            }

            $data = $response->json()['data'] ?? [];

            if (!($data['is_valid'] ?? false)) {
                return ['success' => false, 'error' => 'Token is invalid'];
            }

            return [
                'success' => true,
                'app_id' => $data['app_id'],
                'expires_at' => $data['expires_at'] ?? null,
                'scopes' => $data['scopes'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Facebook validateToken failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
