<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialListeningService
{
    /**
     * Fetch recent mentions from Twitter.
     */
    public function getTwitterMentions(SocialAccount $account, int $count = 20): array
    {
        try {
            $url = "https://api.twitter.com/2/users/{$account->platform_account_id}/mentions";
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->get($url, [
                    'max_results' => $count,
                    'tweet.fields' => 'created_at,public_metrics,author_id',
                    'expansions' => 'author_id',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Failed to fetch mentions',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter mentions fetch failed: '.$e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch recent comments from Facebook page.
     */
    public function getFacebookComments(SocialAccount $account, int $count = 20): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$account->platform_account_id}/feed";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'message,comments.limit('.$count.'){message,from,created_time}',
                'limit' => $count,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Failed to fetch comments',
            ];
        } catch (\Exception $e) {
            Log::error('Facebook comments fetch failed: '.$e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch recent Instagram comments.
     */
    public function getInstagramComments(SocialAccount $account, int $count = 20): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$account->platform_account_id}/media";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'caption,comments.limit('.$count.'){text,username,timestamp}',
                'limit' => $count,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Failed to fetch comments',
            ];
        } catch (\Exception $e) {
            Log::error('Instagram comments fetch failed: '.$e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch data from the appropriate platform.
     */
    public function fetchPlatformData(SocialAccount $account, int $count = 20): array
    {
        return match ($account->platform) {
            'twitter' => $this->getTwitterMentions($account, $count),
            'facebook' => $this->getFacebookComments($account, $count),
            'instagram' => $this->getInstagramComments($account, $count),
            default => ['success' => false, 'error' => "Listening not supported for {$account->platform}"],
        };
    }
}
