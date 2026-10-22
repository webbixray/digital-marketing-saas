<?php

namespace App\Services\Analytics;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\SocialPlatformManager;
use Illuminate\Support\Facades\Log;

/**
 * Fetches and stores platform metrics via the central driver registry.
 *
 * Uses SocialPlatformManager so every registered platform is supported without
 * per-platform method calls here — the driver owns the platform-specific shape.
 */
class PlatformMetricsService
{
    public function __construct(private readonly SocialPlatformManager $platforms) {}

    /**
     * Fetch metrics from the platform API and store them locally.
     */
    public function fetchAndStoreMetrics(SocialAccount $account): void
    {
        if (! $this->platforms->has($account->platform)) {
            return;
        }

        try {
            $metrics = $this->platforms
                ->for($account->platform)
                ->fetchMetrics($account);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch platform metrics', [
                'platform' => $account->platform,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (empty($metrics)) {
            return;
        }

        $this->updatePostMetrics($account, $metrics);
        $this->updateAccountMetadata($account, $metrics);
    }

    /**
     * Update social_posts with latest engagement metrics.
     *
     * @param  array<string, mixed>  $metrics
     */
    private function updatePostMetrics(SocialAccount $account, array $metrics): void
    {
        $posts = SocialPost::where('social_account_id', $account->id)
            ->where('status', 'published')
            ->get();

        foreach ($posts as $post) {
            // Match metrics to post by the external platform identifier.
            if (! empty($post->external_post_id) && isset($metrics['posts'][$post->external_post_id])) {
                $postMetrics = $metrics['posts'][$post->external_post_id];
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

    /**
     * Update the account's follower metadata.
     *
     * @param  array<string, mixed>  $metrics
     */
    private function updateAccountMetadata(SocialAccount $account, array $metrics): void
    {
        $followers = $metrics['followers_count'] ?? $metrics['follower_count'] ?? null;

        if ($followers === null) {
            return;
        }

        $metadata = $account->metadata ?? [];
        $metadata['followers_count'] = $followers;
        $metadata['last_synced_at'] = now()->toISOString();
        $account->update(['metadata' => $metadata]);
    }
}
