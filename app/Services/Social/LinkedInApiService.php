<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInApiService
{
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected string $baseUrl = 'https://api.linkedin.com/v2';

    public function __construct()
    {
        $this->clientId = config('services.linkedin.client_id', env('LINKEDIN_CLIENT_ID', ''));
        $this->clientSecret = config('services.linkedin.client_secret', env('LINKEDIN_CLIENT_SECRET', ''));
    }

    /**
     * Get LinkedIn authorization URL.
     */
    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        $defaultScopes = ['openid', 'profile', 'email', 'w_member_social', 'r_organization_social', 'w_organization_social', 'r_organization_admin'];
        $allScopes = array_unique(array_merge($defaultScopes, $scopes));

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $allScopes),
            'state' => $state,
        ]);

        return "https://www.linkedin.com/oauth/v2/authorization?{$params}";
    }

    /**
     * Exchange authorization code for an access token.
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post("https://www.linkedin.com/oauth/v2/accessToken", [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error('LinkedIn token exchange failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token exchange failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 5184000,
                'scope' => $data['scope'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn token exchange failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get LinkedIn user profile.
     */
    public function getUserProfile(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/userinfo");

            if (!$response->successful()) {
                Log::error('LinkedIn getUserProfile failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch profile'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('LinkedIn getUserProfile failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get LinkedIn organizations (companies) the user can manage.
     */
    public function getOrganizations(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/organizationAcls", [
                    'q' => 'roleAssignee',
                    'projection' => '(elements*(organization~(id,name,vanityName,logoV2)))',
                    'count' => 100,
                ]);

            if (!$response->successful()) {
                Log::error('LinkedIn getOrganizations failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch organizations'];
            }

            $organizations = [];
            foreach ($response->json()['elements'] ?? [] as $element) {
                $org = $element['organization~'] ?? [];
                $organizations[] = [
                    'id' => $element['organization'] ?? null,
                    'name' => $org['name']['localized']['en_US'] ?? $org['name'] ?? null,
                    'vanity_name' => $org['vanityName'] ?? null,
                    'logo_url' => $org['logoV2']['original'] ?? null,
                ];
            }

            return ['success' => true, 'organizations' => $organizations];
        } catch (\Exception $e) {
            Log::error('LinkedIn getOrganizations failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Share a post on behalf of the user or organization.
     */
    public function share(string $accessToken, string $authorUrn, string $text, array $options = []): array
    {
        try {
            $payload = [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => ['text' => $text],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => $options['visibility'] ?? 'PUBLIC',
                ],
            ];

            // Handle media (article link or image)
            if (isset($options['media_url'])) {
                if (($options['media_type'] ?? '') === 'article') {
                    $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                    $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                        [
                            'status' => 'READY',
                            'originalUrl' => $options['media_url'],
                            'title' => ['text' => $options['title'] ?? ''],
                            'description' => ['text' => $options['description'] ?? ''],
                        ],
                    ];
                }
            }

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/ugcPosts", $payload);

            if (!$response->successful()) {
                Log::error('LinkedIn share failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => $response->json()['message'] ?? 'Failed to share'];
            }

            return [
                'success' => true,
                'post_id' => $response->headers()['X-RestLi-Id'][0] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn share failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get share/post statistics.
     */
    public function getShareStats(string $accessToken, string $shareUrn): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/socialActions/{$shareUrn}/comments");

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to fetch share stats'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('LinkedIn getShareStats failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get organization statistics.
     */
    public function getOrganizationStats(string $accessToken, string $organizationUrn): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/organizationalEntityFollowerStatistics", [
                    'q' => 'organizationalEntity',
                    'organizationalEntity' => $organizationUrn,
                    'timeIntervals' => '(start:1609459200000,end:1704067200000,timeGranularityType:DAY)',
                ]);

            if (!$response->successful()) {
                Log::error('LinkedIn getOrganizationStats failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Failed to fetch organization stats'];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('LinkedIn getOrganizationStats failed: ' . $e->getMessage());
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
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post("https://www.linkedin.com/oauth/v2/accessToken", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (!$response->successful()) {
                Log::error('LinkedIn token refresh failed', ['response' => $response->json()]);
                return ['success' => false, 'error' => 'Token refresh failed'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 5184000,
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn token refresh failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate the access token.
     */
    public function validateToken(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/userinfo");

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Token is invalid'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'sub' => $data['sub'] ?? null,
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'picture' => $data['picture'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn validateToken failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
