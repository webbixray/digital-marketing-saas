<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class SocialPlatformApi
{
    protected string $accessToken;

    protected string $baseUrl;

    public function __construct(string $accessToken = '')
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Get the authorization URL for OAuth flow.
     */
    abstract public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string;

    /**
     * Exchange authorization code for access token.
     */
    abstract public function exchangeCodeForToken(string $code, string $redirectUri): array;

    /**
     * Refresh an expired access token.
     */
    abstract public function refreshToken(string $refreshToken): array;

    /**
     * Make an authenticated API request.
     */
    protected function request(string $method, string $endpoint, array $params = []): array
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->{$method}($url, $params);

            if (! $response->successful()) {
                Log::error('Social API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return ['success' => false, 'error' => 'API request failed'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('Social API exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Make an authenticated GET request.
     */
    protected function get(string $endpoint, array $params = []): array
    {
        return $this->request('get', $endpoint, $params);
    }

    /**
     * Make an authenticated POST request.
     */
    protected function post(string $endpoint, array $params = []): array
    {
        return $this->request('post', $endpoint, $params);
    }
}
