<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PinterestApiService
{
    protected ?string $appId;
    protected ?string $appSecret;
    protected string $baseUrl = 'https://api.pinterest.com/v5';

    public function __construct()
    {
        $this->appId = config('services.pinterest.app_id', env('PINTEREST_APP_ID', ''));
        $this->appSecret = config('services.pinterest.app_secret', env('PINTEREST_APP_SECRET', ''));
    }

    /**
     * Get Pinterest authorization URL.
     */
    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        $defaultScopes = ['boards:read', 'boards:write', 'pins:read', 'pins:write', 'user_accounts:read'];
        $allScopes = array_unique(array_merge($defaultScopes, $scopes));

        $params = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(',', $allScopes),
            'state' => $state,
        ]);

        return "https://www.pinterest.com/oauth/?{$params}";
    }

    /**
     * Exchange authorization code for an access token.
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->withBasicAuth($this->appId, $this->appSecret)
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error('Pinterest token exchange failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token exchange failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 3600,
                'scope' => $data['scope'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest token exchange failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Pinterest user profile.
     */
    public function getUserProfile(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/user_account");

            if (!$response->successful()) {
                Log::error('Pinterest getUserProfile failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch profile'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Pinterest getUserProfile failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's boards.
     */
    public function getBoards(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/boards", [
                    'page_size' => 100,
                ]);

            if (!$response->successful()) {
                Log::error('Pinterest getBoards failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch boards'];
            }

            return [
                'success' => true,
                'boards' => $response->json()['items'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getBoards failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get pins from a board.
     */
    public function getPins(string $accessToken, string $boardId, int $pageSize = 100): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/boards/{$boardId}/pins", [
                    'page_size' => $pageSize,
                ]);

            if (!$response->successful()) {
                Log::error('Pinterest getPins failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch pins'];
            }

            return [
                'success' => true,
                'pins' => $response->json()['items'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest getPins failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a pin.
     */
    public function createPin(string $accessToken, string $boardId, string $title, string $description, string $imageUrl, ?string $link = null): array
    {
        try {
            $payload = [
                'board_id' => $boardId,
                'title' => $title,
                'description' => $description,
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $imageUrl,
                ],
            ];

            if ($link) {
                $payload['link'] = $link;
            }

            $response = Http::withToken($accessToken)
                ->timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/pins", $payload);

            if (!$response->successful()) {
                Log::error('Pinterest createPin failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['message'] ?? 'Failed to create pin'];
            }

            return [
                'success' => true,
                'pin_id' => $response->json()['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest createPin failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get pin analytics.
     */
    public function getPinAnalytics(string $accessToken, string $pinId): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/pins/{$pinId}/analytics");

            if (!$response->successful()) {
                Log::error('Pinterest getPinAnalytics failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch analytics'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Pinterest getPinAnalytics failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a pin.
     */
    public function deletePin(string $accessToken, string $pinId): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->delete("{$this->baseUrl}/pins/{$pinId}");

            if (!$response->successful()) {
                Log::error('Pinterest deletePin failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to delete pin'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Pinterest deletePin failed: ' . $e->getMessage());
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
                ->withBasicAuth($this->appId, $this->appSecret)
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (!$response->successful()) {
                Log::error('Pinterest token refresh failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token refresh failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 3600,
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest refreshToken failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate the access token.
     */
    public function validateToken(string $accessToken): array
    {
        $result = $this->getUserProfile($accessToken);
        return $result['success']
            ? ['success' => true, 'user' => $result['data']]
            : $result;
    }
}
