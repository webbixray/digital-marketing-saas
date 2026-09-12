<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialApiService
{
    protected string $facebookApiVersion = 'v18.0';

    protected string $twitterApiVersion = '2';

    /**
     * Publish post to Facebook
     */
    public function publishToFacebook(SocialAccount $account, SocialPost $post): array
    {
        try {
            $url = "https://graph.facebook.com/{$this->facebookApiVersion}/{$account->account_id}/feed";

            $response = Http::timeout(30)->post($url, [
                'message' => $post->content,
                'access_token' => $account->access_token,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_post_id' => $response->json()['id'] ?? null,
                    'url' => "https://facebook.com/{$response->json()['id']}",
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Facebook publish failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish post to Instagram
     */
    public function publishToInstagram(SocialAccount $account, SocialPost $post): array
    {
        try {
            // Instagram requires media - create container first
            $containerUrl = "https://graph.facebook.com/{$this->facebookApiVersion}/{$account->account_id}/media";

            $containerResponse = Http::timeout(30)->post($containerUrl, [
                'access_token' => $account->access_token,
                'caption' => $post->content,
            ]);

            if (! $containerResponse->successful()) {
                return [
                    'success' => false,
                    'error' => $containerResponse->json()['error']['message'] ?? 'Container creation failed',
                ];
            }

            $creationId = $containerResponse->json()['id'];

            // Publish container
            $publishUrl = "https://graph.facebook.com/{$this->facebookApiVersion}/{$account->account_id}/media_publish";
            $publishResponse = Http::timeout(30)->post($publishUrl, [
                'creation_id' => $creationId,
                'access_token' => $account->access_token,
            ]);

            if ($publishResponse->successful()) {
                return [
                    'success' => true,
                    'platform_post_id' => $publishResponse->json()['id'] ?? null,
                    'url' => "https://instagram.com/p/{$publishResponse->json()['id']}",
                ];
            }

            return [
                'success' => false,
                'error' => $publishResponse->json()['error']['message'] ?? 'Publish failed',
            ];
        } catch (\Exception $e) {
            Log::error('Instagram publish failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish post to Twitter/X
     */
    public function publishToTwitter(SocialAccount $account, SocialPost $post): array
    {
        try {
            $url = "https://api.twitter.com/{$this->twitterApiVersion}/tweets";

            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->post($url, ['text' => $post->content]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_post_id' => $response->json()['data']['id'] ?? null,
                    'url' => "https://twitter.com/i/web/status/{$response->json()['data']['id']}",
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter publish failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish post to LinkedIn
     */
    public function publishToLinkedIn(SocialAccount $account, SocialPost $post): array
    {
        try {
            $url = 'https://api.linkedin.com/v2/ugcPosts';

            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post($url, [
                    'author' => "urn:li:person:{$account->account_id}",
                    'lifecycleState' => 'PUBLISHED',
                    'specificContent' => [
                        'com.linkedin.ugc.ShareContent' => [
                            'shareCommentary' => ['text' => $post->content],
                            'shareMediaCategory' => 'NONE',
                        ],
                    ],
                    'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_post_id' => $response->headers()['X-RestLi-Id'][0] ?? null,
                    'url' => "https://www.linkedin.com/feed/update/urn:li:activity:{$response->headers()['X-RestLi-Id'][0]}",
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn publish failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish post to TikTok
     */
    public function publishToTikTok(SocialAccount $account, SocialPost $post): array
    {
        try {
            $url = 'https://open-api.tiktok.com/share/video/upload/';

            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->post($url, [
                    'text' => $post->content,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_post_id' => $response->json()['data']['share_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('TikTok publish failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish post to Pinterest
     */
    public function publishToPinterest(SocialAccount $account, SocialPost $post): array
    {
        try {
            $url = 'https://api.pinterest.com/v5/pins';

            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'title' => Str::limit($post->content, 100),
                    'description' => $post->content,
                    'board_id' => $account->metadata['board_id'] ?? null,
                    'link' => $post->links['url'] ?? null,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_post_id' => $response->json()['id'] ?? null,
                    'url' => $response->json()['link'] ?? "https://pinterest.com/pin/{$response->json()['id']}",
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest publish failed: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish to the appropriate platform based on account type
     */
    public function publish(SocialAccount $account, SocialPost $post): array
    {
        return match ($account->platform) {
            'facebook' => $this->publishToFacebook($account, $post),
            'instagram' => $this->publishToInstagram($account, $post),
            'twitter' => $this->publishToTwitter($account, $post),
            'linkedin' => $this->publishToLinkedIn($account, $post),
            'tiktok' => $this->publishToTikTok($account, $post),
            'pinterest' => $this->publishToPinterest($account, $post),
            default => ['success' => false, 'error' => "Unsupported platform: {$account->platform}"],
        };
    }

    /**
     * Get account info from platform
     */
    public function getAccountInfo(SocialAccount $account): array
    {
        return match ($account->platform) {
            'facebook' => $this->getFacebookAccountInfo($account),
            'twitter' => $this->getTwitterAccountInfo($account),
            default => ['success' => false, 'error' => 'Not implemented'],
        };
    }

    protected function getFacebookAccountInfo(SocialAccount $account): array
    {
        try {
            $url = "https://graph.facebook.com/{$this->facebookApiVersion}/{$account->account_id}";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'name,followers_count,engagement',
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getTwitterAccountInfo(SocialAccount $account): array
    {
        try {
            $url = "https://api.twitter.com/{$this->twitterApiVersion}/users/by/username/{$account->username}";
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->get($url, ['user.fields' => 'public_metrics']);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->json()['detail'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
