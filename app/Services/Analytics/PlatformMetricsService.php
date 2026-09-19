<?php

namespace App\Services\Analytics;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\FacebookApiService;
use App\Services\Social\InstagramApiService;
use App\Services\Social\TwitterApiService;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\TikTokApiService;
use App\Services\Social\PinterestApiService;
use App\Services\Social\YouTubeApiService;
use Illuminate\Support\Facades\Log;

class PlatformMetricsService
{
    public function __construct(
        private readonly FacebookApiService $facebook,
        private readonly InstagramApiService $instagram,
        private readonly TwitterApiService $twitter,
        private readonly LinkedInApiService $linkedin,
        private readonly TikTokApiService $tiktok,
        private readonly PinterestApiService $pinterest,
        private readonly YouTubeApiService $youtube,
    ) {}

    /**
     * Fetch metrics from the platform API and store them locally.
     */
    public function fetchAndStoreMetrics(SocialAccount $account): void
    {
        try {
            $metrics = match ($account->platform) {
                'facebook' => $this->fetchFacebookMetrics($account),
                'instagram' => $this->fetchInstagramMetrics($account),
                'twitter' => $this->fetchTwitterMetrics($account),
                'linkedin' => $this->fetchLinkedInMetrics($account),
                'tiktok' => $this->fetchTikTokMetrics($account),
                'pinterest' => $this->fetchPinterestMetrics($account),
                'youtube' => $this->fetchYouTubeMetrics($account),
                default => null,
            };

            if ($metrics) {
                $this->updatePostMetrics($account, $metrics);
                $this->updateAccountMetadata($account, $metrics);
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch platform metrics', [
                'platform' => $account->platform,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function fetchFacebookMetrics(SocialAccount $account): ?array
    {
        try {
            $insights = $this->facebook->getPageInsights(
                $account->platform_account_id,
                $account->access_token
            );
            return $insights['data'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Facebook metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchInstagramMetrics(SocialAccount $account): ?array
    {
        try {
            $insights = $this->instagram->getAccountInsights(
                $account->platform_account_id,
                $account->access_token
            );
            return $insights['data'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Instagram metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchTwitterMetrics(SocialAccount $account): ?array
    {
        try {
            $metrics = $this->twitter->getUserMetrics(
                $account->platform_account_id,
                $account->access_token
            );
            return $metrics;
        } catch (\Exception $e) {
            Log::warning('Twitter metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchLinkedInMetrics(SocialAccount $account): ?array
    {
        try {
            $stats = $this->linkedin->getOrganizationStats(
                $account->platform_account_id,
                $account->access_token
            );
            return $stats;
        } catch (\Exception $e) {
            Log::warning('LinkedIn metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchTikTokMetrics(SocialAccount $account): ?array
    {
        try {
            $stats = $this->tiktok->getUserStats(
                $account->platform_account_id,
                $account->access_token
            );
            return $stats;
        } catch (\Exception $e) {
            Log::warning('TikTok metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchPinterestMetrics(SocialAccount $account): ?array
    {
        try {
            $analytics = $this->pinterest->getUserAnalytics(
                $account->platform_account_id,
                $account->access_token
            );
            return $analytics;
        } catch (\Exception $e) {
            Log::warning('Pinterest metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchYouTubeMetrics(SocialAccount $account): ?array
    {
        try {
            $analytics = $this->youtube->getChannelAnalytics(
                $account->platform_account_id,
                $account->access_token
            );
            return $analytics;
        } catch (\Exception $e) {
            Log::warning('YouTube metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function updatePostMetrics(SocialAccount $account, array $metrics): void
    {
        // Update social_posts with latest engagement metrics
        $posts = SocialPost::where('social_account_id', $account->id)
            ->where('status', 'published')
            ->get();

        foreach ($posts as $post) {
            // Match metrics to post by platform_post_id if available
            if (!empty($post->platform_post_id) && isset($metrics['posts'][$post->platform_post_id])) {
                $postMetrics = $metrics['posts'][$post->platform_post_id];
                $post->update([
                    'metrics' => array_merge($post->metrics ?? [], [
                        'likes' => $postMetrics['likes'] ?? 0,
                        'comments' => $postMetrics['comments'] ?? 0,
                        'shares' => $postMetrics['shares'] ?? 0,
                        'impressions' => $postMetrics['impressions'] ?? 0,
                        'reach' => $postMetrics['reach'] ?? 0,
                        'engagement_rate' => $postMetrics['engagement_rate'] ?? 0,
                        'synced_at' => now()->toISOString(),
                    ]),
                ]);
            }
        }
    }

    private function updateAccountMetadata(SocialAccount $account, array $metrics): void
    {
        if (isset($metrics['followers_count']) || isset($metrics['follower_count'])) {
            $metadata = $account->metadata ?? [];
            $metadata['followers_count'] = $metrics['followers_count'] ?? $metrics['follower_count'] ?? 0;
            $metadata['last_synced_at'] = now()->toISOString();
            $account->update(['metadata' => $metadata]);
        }
    }
}
