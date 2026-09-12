<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeApiService
{
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $apiKey;
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3';
    protected string $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';

    public function __construct()
    {
        $this->clientId = config('services.youtube.client_id', env('YOUTUBE_CLIENT_ID', ''));
        $this->clientSecret = config('services.youtube.client_secret', env('YOUTUBE_CLIENT_SECRET', ''));
        $this->apiKey = config('services.youtube.api_key', env('YOUTUBE_API_KEY', ''));
    }

    /**
     * Get YouTube authorization URL.
     */
    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        $defaultScopes = [
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube.force-ssl',
            'https://www.googleapis.com/auth/youtubepartner',
            'https://www.googleapis.com/auth/youtube.channel-memberships.creator',
        ];
        $allScopes = array_unique(array_merge($defaultScopes, $scopes));

        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $allScopes),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ]);

        return "{$this->authUrl}?{$params}";
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->tokenUrl, [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error('YouTube token exchange failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token exchange failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 3600,
                'scope' => $data['scope'] ?? '',
                'token_type' => $data['token_type'] ?? 'Bearer',
            ];
        } catch (\Exception $e) {
            Log::error('YouTube token exchange failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get YouTube channel info for the authenticated user.
     */
    public function getMyChannel(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/channels", [
                    'part' => 'snippet,contentDetails,statistics,status',
                    'mine' => 'true',
                ]);

            if (!$response->successful()) {
                Log::error('YouTube getMyChannel failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch channel'];
            }

            $data = $response->json();
            $channel = $data['items'][0] ?? null;

            if (!$channel) {
                return ['success' => false, 'error' => 'No channel found'];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $channel['id'],
                    'title' => $channel['snippet']['title'] ?? null,
                    'description' => $channel['snippet']['description'] ?? null,
                    'custom_url' => $channel['snippet']['customUrl'] ?? null,
                    'thumbnail' => $channel['snippet']['thumbnails']['high']['url'] ?? null,
                    'subscriber_count' => (int) ($channel['statistics']['subscriberCount'] ?? 0),
                    'video_count' => (int) ($channel['statistics']['videoCount'] ?? 0),
                    'view_count' => (int) ($channel['statistics']['viewCount'] ?? 0),
                    'comment_count' => (int) ($channel['statistics']['commentCount'] ?? 0),
                    'published_at' => $channel['snippet']['publishedAt'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('YouTube getMyChannel failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get channel statistics.
     */
    public function getChannelStats(string $accessToken, string $channelId): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/channels", [
                    'part' => 'statistics,topicDetails',
                    'id' => $channelId,
                ]);

            if (!$response->successful()) {
                Log::error('YouTube getChannelStats failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch stats'];
            }

            $data = $response->json();
            $channel = $data['items'][0] ?? null;

            if (!$channel) {
                return ['success' => false, 'error' => 'Channel not found'];
            }

            return [
                'success' => true,
                'data' => [
                    'subscriber_count' => (int) ($channel['statistics']['subscriberCount'] ?? 0),
                    'video_count' => (int) ($channel['statistics']['videoCount'] ?? 0),
                    'view_count' => (int) ($channel['statistics']['viewCount'] ?? 0),
                    'comment_count' => (int) ($channel['statistics']['commentCount'] ?? 0),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('YouTube getChannelStats failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get videos from the authenticated user's channel.
     */
    public function getMyVideos(string $accessToken, int $maxResults = 50, ?string $pageToken = null): array
    {
        try {
            $params = [
                'part' => 'snippet,contentDetails,statistics',
                'mine' => 'true',
                'maxResults' => min($maxResults, 50),
                'order' => 'date',
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/videos", $params);

            if (!$response->successful()) {
                Log::error('YouTube getMyVideos failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch videos'];
            }

            $data = $response->json();

            $videos = [];
            foreach ($data['items'] ?? [] as $item) {
                $videos[] = [
                    'id' => $item['id'],
                    'title' => $item['snippet']['title'] ?? '',
                    'description' => $item['snippet']['description'] ?? '',
                    'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                    'published_at' => $item['snippet']['publishedAt'] ?? null,
                    'duration' => $item['contentDetails']['duration'] ?? null,
                    'view_count' => (int) ($item['statistics']['viewCount'] ?? 0),
                    'like_count' => (int) ($item['statistics']['likeCount'] ?? 0),
                    'comment_count' => (int) ($item['statistics']['commentCount'] ?? 0),
                ];
            }

            return [
                'success' => true,
                'videos' => $videos,
                'next_page_token' => $data['nextPageToken'] ?? null,
                'prev_page_token' => $data['prevPageToken'] ?? null,
                'total_results' => $data['pageInfo']['totalResults'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('YouTube getMyVideos failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload video to YouTube.
     */
    public function uploadVideo(string $accessToken, $file, string $title, string $description = '', array $options = []): array
    {
        try {
            $metadata = [
                'snippet' => [
                    'title' => $title,
                    'description' => $description,
                    'categoryId' => $options['category_id'] ?? '22', // People & Blogs
                    'tags' => $options['tags'] ?? [],
                    'defaultLanguage' => $options['language'] ?? 'en',
                ],
                'status' => [
                    'privacyStatus' => $options['privacy'] ?? 'private',
                    'selfDeclaredMadeForKids' => $options['made_for_kids'] ?? false,
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(300)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/videos?part=snippet,status", $metadata);

            if (!$response->successful()) {
                Log::error('YouTube uploadVideo failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to upload'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'video_id' => $data['id'],
                'title' => $data['snippet']['title'] ?? $title,
                'url' => "https://www.youtube.com/watch?v={$data['id']}",
            ];
        } catch (\Exception $e) {
            Log::error('YouTube uploadVideo failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get video analytics.
     */
    public function getVideoAnalytics(string $accessToken, string $videoId): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/videos", [
                    'part' => 'statistics,topicDetails',
                    'id' => $videoId,
                ]);

            if (!$response->successful()) {
                Log::error('YouTube getVideoAnalytics failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch video analytics'];
            }

            $data = $response->json();
            $video = $data['items'][0] ?? null;

            if (!$video) {
                return ['success' => false, 'error' => 'Video not found'];
            }

            return [
                'success' => true,
                'data' => [
                    'view_count' => (int) ($video['statistics']['viewCount'] ?? 0),
                    'like_count' => (int) ($video['statistics']['likeCount'] ?? 0),
                    'comment_count' => (int) ($video['statistics']['commentCount'] ?? 0),
                    'favorite_count' => (int) ($video['statistics']['favoriteCount'] ?? 0),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('YouTube getVideoAnalytics failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->tokenUrl, [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (!$response->successful()) {
                Log::error('YouTube token refresh failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token refresh failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'] ?? 3600,
            ];
        } catch (\Exception $e) {
            Log::error('YouTube token refresh failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate access token.
     */
    public function validateToken(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/channels", [
                    'part' => 'id',
                    'mine' => 'true',
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Token is invalid'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('YouTube validateToken failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
