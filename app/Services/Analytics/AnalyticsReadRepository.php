<?php

namespace App\Services\Analytics;

use App\Models\Agency;
use App\Models\SocialPost;
use App\Concerns\ReadReplica;

/**
 * AnalyticsReadRepository provides read-optimized access to analytics data.
 *
 * All queries in this repository run against the configured read replica
 * database connection to offload analytical workloads from the primary
 * database. This implements the read side of CQRS for reporting.
 *
 * Writes (creating reports, updating status, etc.) continue to go through
 * the primary connection via the standard Eloquent models.
 */
class AnalyticsReadRepository
{
    use ReadReplica;

    /**
     * Return all core dashboard stats for an agency from the read replica.
     *
     * @return array<string, mixed>
     */
    public function getDashboardStats(Agency $agency, int $days = 30): array
    {
        return $this->read(function () use ($agency, $days) {
            return [
                'overview' => $this->getOverviewStats($agency),
                'social' => $this->getSocialStats($agency),
                'daily_engagement' => $this->getDailyEngagement($agency, $days),
                'best_posts' => $this->getBestPosts($agency),
            ];
        });
    }

    /**
     * High-level overview stats from the read replica.
     *
     * @return array<string, mixed>
     */
    public function getOverviewStats(Agency $agency): array
    {
        return $this->read(function () use ($agency) {
            return [
                'total_posts' => SocialPost::where('agency_id', $agency->id)->count(),
            ];
        });
    }

    /**
     * Social media stats from the read replica.
     *
     * @return array<string, mixed>
     */
    public function getSocialStats(Agency $agency): array
    {
        return $this->read(function () use ($agency) {
            $stats = SocialPost::where('agency_id', $agency->id)
                ->selectRaw('
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
                    AVG(CASE WHEN status = "published" THEN engagement_rate ELSE NULL END) as average_engagement,
                    SUM(engagement_rate) as total_engagement
                ')
                ->first();

            return [
                'total_posts' => (int) ($stats->total_posts ?? 0),
                'published' => (int) ($stats->published ?? 0),
                'scheduled' => (int) ($stats->scheduled ?? 0),
                'failed' => (int) ($stats->failed ?? 0),
                'draft' => (int) ($stats->draft ?? 0),
                'average_engagement' => round((float) ($stats->average_engagement ?? 0), 2),
                'total_engagement' => round((float) ($stats->total_engagement ?? 0), 2),
                'by_platform' => $this->groupByPlatform($agency),
            ];
        });
    }

    /**
     * Daily engagement over time from the read replica.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDailyEngagement(Agency $agency, int $days = 30): array
    {
        return $this->read(function () use ($agency, $days) {
            return SocialPost::where('agency_id', $agency->id)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays($days))
                ->selectRaw('
                    DATE(published_at) as date,
                    COALESCE(SUM(likes_count + comments_count + shares_count), 0) as engagement
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Best performing posts from the read replica.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBestPosts(Agency $agency, int $limit = 5): array
    {
        return $this->read(function () use ($agency, $limit) {
            return SocialPost::where('agency_id', $agency->id)
                ->where('status', 'published')
                ->orderBy('likes_count', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn ($post) => [
                    'id' => $post->id,
                    'platform' => $post->platform,
                    'content' => $post->content,
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->comments_count,
                    'shares_count' => $post->shares_count,
                    'views_count' => $post->views_count,
                    'published_at' => $post->published_at?->toDateTimeString(),
                ])
                ->toArray();
        });
    }

    /**
     * Get posts grouped by platform from the read replica.
     *
     * @return array<string, int>
     */
    private function groupByPlatform(Agency $agency): array
    {
        return $this->read(function () use ($agency) {
            return SocialPost::where('agency_id', $agency->id)
                ->selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform')
                ->toArray();
        });
    }
}
