<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokApiService
{
    protected ?string $clientKey;
    protected ?string $clientSecret;
    protected string $baseUrl = 'https://open.tiktokapis.com/v2';

    public function __construct()
    {
        $this->clientKey = config('services.tiktok.client_key', env('TIKTOK_CLIENT_KEY', ''));
        $this->clientSecret = config('services.tiktok.client_secret', env('TIKTOK_CLIENT_SECRET', ''));
    }

    /**
     * Get TikTok authorization URL.
     */
    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        $defaultScopes = ['user.info.basic', 'user.info.profile', 'user.info.stats', 'video.publish', 'video.list', 'comment.list', 'comment.manage'];
        $allScopes = array_unique(array_merge($defaultScopes, $scopes));

        $params = http_build_query([
            'client_key' => $this->clientKey,
            'response_type' => 'code',
            'scope' => implode(',', $allScopes),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return "https://www.tiktok.com/v2/auth/authorize?{$params}";
    }

    /**
     * Exchange authorization code for an access token.
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/token/", [
                    'client_key' => $this->clientKey,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error('TikTok token exchange failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token exchange failed'];
            }

            $data = $response->json();

            if ($data['error_code'] ?? 0 !== 0) {
                Log::error('TikTok token error', ['error' => $data]);
                return ['success' => false, 'error' => $data['message'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'access_token' => $data['data']['access_token'],
                'refresh_token' => $data['data']['refresh_token'],
                'expires_in' => $data['data']['expires_in'] ?? 86400,
                'refresh_expires_in' => $data['data']['refresh_expires_in'] ?? 86400,
                'scope' => $data['data']['scope'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok token exchange failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get TikTok user info.
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/user/info/", [
                    'fields' => 'open_id,union_id,avatar_url,display_name,username,bio_description,follower_count,following_count,likes_count,video_count',
                ]);

            if (!$response->successful()) {
                Log::error('TikTok getUserInfo failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch user info'];
            }

            $data = $response->json();
            if ($data['error']['code'] ?? '' !== 'ok') {
                return ['success' => false, 'error' => $data['error']['message'] ?? 'Unknown error'];
            }

            return ['success' => true, 'data' => $data['data']['user'] ?? []];
        } catch (\Exception $e) {
            Log::error('TikTok getUserInfo failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's videos.
     */
    public function getVideos(string $accessToken, int $cursor = 0, int $maxCount = 20): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/video/list/", [
                    'fields' => 'id,title,cover_image_url,embed_html,create_time,like_count,comment_count,share_count,view_count',
                    'cursor' => $cursor,
                    'max_count' => $maxCount,
                ]);

            if (!$response->successful()) {
                Log::error('TikTok getVideos failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch videos'];
            }

            $data = $response->json();
            return [
                'success' => true,
                'videos' => $data['data']['videos'] ?? [],
                'has_more' => $data['data']['has_more'] ?? false,
                'cursor' => $data['data']['cursor'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('TikTok getVideos failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get video statistics.
     */
    public function getVideoStats(string $accessToken, string $videoId): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/video/list/", [
                    'fields' => 'id,title,cover_image_url,like_count,comment_count,share_count,view_count,create_time',
                    'video_ids' => [$videoId],
                ]);

            if (!$response->successful()) {
                Log::error('TikTok getVideoStats failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch video stats'];
            }

            $data = $response->json();
            $videos = $data['data']['videos'] ?? [];

            return [
                'success' => true,
                'data' => $videos[0] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('TikTok getVideoStats failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish video via URL (Content Posting API).
     */
    public function publishVideo(string $accessToken, string $videoUrl, string $title, ?string $description = null, array $options = []): array
    {
        try {
            $payload = [
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $videoUrl,
                ],
                'title' => $title,
            ];

            if ($description) {
                $payload['description'] = $description;
            }

            if (isset($options['allow_comment'])) {
                $payload['disable_comment'] = !$options['allow_comment'];
            }
            if (isset($options['allow_duet'])) {
                $payload['disable_duet'] = !$options['allow_duet'];
            }
            if (isset($options['allow_stitch'])) {
                $payload['disable_stitch'] = !$options['allow_stitch'];
            }

            $response = Http::withToken($accessToken)
                ->timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/publish/video/", $payload);

            if (!$response->successful()) {
                Log::error('TikTok publishVideo failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to publish'];
            }

            $data = $response->json();
            return [
                'success' => true,
                'publish_id' => $data['data']['publish_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('TikTok publishVideo failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload video directly (PULL_FROM_URL is preferred but can support direct upload).
     */
    public function uploadVideo(string $accessToken, $file, string $title, ?string $description = null): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(120)
                ->attach('video', file_get_contents($file->getPathname()), $file->getFilename())
                ->post("{$this->baseUrl}/publish/video/", [
                    'title' => $title,
                    'description' => $description ?? '',
                ]);

            if (!$response->successful()) {
                Log::error('TikTok uploadVideo failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to upload'];
            }

            $data = $response->json();
            return [
                'success' => true,
                'publish_id' => $data['data']['publish_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('TikTok uploadVideo failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's comments.
     */
    public function getComments(string $accessToken, string $videoId, int $cursor = 0, int $count = 20): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/comment/list/", [
                    'video_id' => $videoId,
                    'fields' => 'id,content,create_time,like_count,reply_count,user',
                    'cursor' => $cursor,
                    'count' => $count,
                ]);

            if (!$response->successful()) {
                Log::error('TikTok getComments failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch comments'];
            }

            $data = $response->json();
            return [
                'success' => true,
                'comments' => $data['data']['comments'] ?? [],
                'has_more' => $data['data']['has_more'] ?? false,
                'cursor' => $data['data']['cursor'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('TikTok getComments failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reply to a comment.
     */
    public function replyToComment(string $accessToken, string $commentId, string $text): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/comment/reply/", [
                    'comment_id' => $commentId,
                    'content' => $text,
                ]);

            if (!$response->successful()) {
                Log::error('TikTok replyToComment failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to reply'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('TikTok replyToComment failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refresh the access token.
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/token/", [
                    'client_key' => $this->clientKey,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (!$response->successful()) {
                Log::error('TikTok token refresh failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token refresh failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['data']['access_token'],
                'refresh_token' => $data['data']['refresh_token'],
                'expires_in' => $data['data']['expires_in'] ?? 86400,
            ];
        } catch (\Exception $e) {
            Log::error('TikTok refreshToken failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate the access token.
     */
    public function validateToken(string $accessToken): array
    {
        $result = $this->getUserInfo($accessToken);
        return $result['success']
            ? ['success' => true, 'user' => $result['data']]
            : $result;
    }
}
