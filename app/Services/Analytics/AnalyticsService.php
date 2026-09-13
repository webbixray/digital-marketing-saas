<?php

namespace App\Services\Analytics;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Return all core dashboard stats for an agency.
     */
    public function getDashboardStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:dashboard", self::CACHE_TTL, function () use ($agency) {
            return [
                'overview' => $this->getOverviewStats($agency),
                'social' => $this->getSocialStats($agency),
                'email' => $this->getEmailStats($agency),
                'financial' => $this->getFinancialStats($agency),
                'ai' => $this->getAiStats($agency),
            ];
        });
    }

    /**
     * High-level overview: clients, posts, campaigns, revenue.
     */
    public function getOverviewStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:overview", self::CACHE_TTL, function () use ($agency) {
            return [
                'total_clients' => Client::where('agency_id', $agency->id)
                    ->where('status', 'active')->count(),
                'total_posts' => SocialPost::where('agency_id', $agency->id)->count(),
                'total_campaigns' => Campaign::where('agency_id', $agency->id)->count(),
                'total_revenue' => Invoice::where('agency_id', $agency->id)
                    ->where('status', 'paid')->sum('total'),
                'pending_invoices' => Invoice::where('agency_id', $agency->id)
                    ->where('status', 'pending')->count(),
                'active_social_accounts' => SocialAccount::where('agency_id', $agency->id)
                    ->where('is_connected', true)->count(),
            ];
        });
    }

    /**
     * Social media stats - optimized with single query using database aggregations.
     */
    public function getSocialStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:social", self::CACHE_TTL, function () use ($agency) {
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
                'total_posts' => (int) $stats->total_posts,
                'published' => (int) $stats->published,
                'scheduled' => (int) $stats->scheduled,
                'failed' => (int) $stats->failed,
                'draft' => (int) $stats->draft,
                'average_engagement' => round((float) ($stats->average_engagement ?? 0), 2),
                'total_engagement' => round((float) ($stats->total_engagement ?? 0), 2),
                'by_platform' => $this->groupByPlatform($agency),
            ];
        });
    }

    /**
     * Email marketing stats - optimized with single query.
     */
    public function getEmailStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:email", self::CACHE_TTL, function () use ($agency) {
            $stats = EmailCampaign::where('agency_id', $agency->id)
                ->selectRaw('
                    COUNT(*) as total_campaigns,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_campaigns,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_campaigns,
                    SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled_campaigns,
                    SUM(sent_count) as total_sent,
                    SUM(opened_count) as total_opened,
                    SUM(clicked_count) as total_clicked,
                    AVG(CASE WHEN status = "sent" THEN open_rate ELSE NULL END) as average_open_rate,
                    AVG(CASE WHEN status = "sent" THEN click_rate ELSE NULL END) as average_click_rate
                ')
                ->first();

            return [
                'total_campaigns' => (int) $stats->total_campaigns,
                'sent_campaigns' => (int) $stats->sent_campaigns,
                'draft_campaigns' => (int) $stats->draft_campaigns,
                'scheduled_campaigns' => (int) $stats->scheduled_campaigns,
                'total_sent' => (int) ($stats->total_sent ?? 0),
                'total_opened' => (int) ($stats->total_opened ?? 0),
                'total_clicked' => (int) ($stats->total_clicked ?? 0),
                'average_open_rate' => round((float) ($stats->average_open_rate ?? 0), 2),
                'average_click_rate' => round((float) ($stats->average_click_rate ?? 0), 2),
            ];
        });
    }

    /**
     * Financial stats - optimized with single query.
     */
    public function getFinancialStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:financial", self::CACHE_TTL, function () use ($agency) {
            $stats = Invoice::where('agency_id', $agency->id)
                ->selectRaw('
                    COUNT(*) as total_invoices,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_invoices,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_invoices,
                    SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_invoices,
                    SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = "pending" THEN total ELSE 0 END) as pending_amounts,
                    SUM(CASE WHEN status = "overdue" THEN total ELSE 0 END) as overdue_amounts
                ')
                ->first();

            return [
                'total_invoices' => (int) $stats->total_invoices,
                'paid_invoices' => (int) $stats->paid_invoices,
                'pending_invoices' => (int) $stats->pending_invoices,
                'overdue_invoices' => (int) $stats->overdue_invoices,
                'total_revenue' => (float) ($stats->total_revenue ?? 0),
                'pending_amounts' => (float) ($stats->pending_amounts ?? 0),
                'overdue_amounts' => (float) ($stats->overdue_amounts ?? 0),
            ];
        });
    }

    /**
     * AI usage stats - optimized with single query.
     */
    public function getAiStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:ai", self::CACHE_TTL, function () use ($agency) {
            $stats = AiContentLog::where('agency_id', $agency->id)
                ->selectRaw('
                    COUNT(*) as total_generations,
                    SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_generations,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_generations,
                    SUM(total_tokens) as total_tokens_used,
                    SUM(cost_usd) as total_cost_usd,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as last_7_days
                ', [now()->subDays(7)])
                ->first();

            return [
                'total_generations' => (int) $stats->total_generations,
                'successful_generations' => (int) $stats->successful_generations,
                'failed_generations' => (int) $stats->failed_generations,
                'total_tokens_used' => (int) ($stats->total_tokens_used ?? 0),
                'total_cost_usd' => (float) ($stats->total_cost_usd ?? 0),
                'by_action' => $this->groupByAction($agency),
                'last_7_days' => (int) ($stats->last_7_days ?? 0),
            ];
        });
    }

    /**
     * Clear cached analytics data for an agency.
     */
    public function clearCache(Agency $agency): void
    {
        Cache::forget("analytics:{$agency->id}:dashboard");
        Cache::forget("analytics:{$agency->id}:overview");
        Cache::forget("analytics:{$agency->id}:social");
        Cache::forget("analytics:{$agency->id}:email");
        Cache::forget("analytics:{$agency->id}:financial");
        Cache::forget("analytics:{$agency->id}:ai");
    }

    /**
     * Get total posts count scoped to agency.
     */
    public function getTotalPosts(Agency $agency): int
    {
        return SocialPost::where('agency_id', $agency->id)->count();
    }

    /**
     * Get total campaigns count scoped to agency.
     */
    public function getTotalCampaigns(Agency $agency): int
    {
        return EmailCampaign::where('agency_id', $agency->id)->count();
    }

    /**
     * Get total invoices count scoped to agency.
     */
    public function getTotalInvoices(Agency $agency): int
    {
        return Invoice::where('agency_id', $agency->id)->count();
    }

    /**
     * Get posts grouped by platform.
     */
    private function groupByPlatform(Agency $agency): array
    {
        return SocialPost::where('agency_id', $agency->id)
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();
    }

    /**
     * Get AI logs grouped by action.
     */
    private function groupByAction(Agency $agency): array
    {
        return AiContentLog::where('agency_id', $agency->id)
            ->selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();
    }

    /**
     * Get logs grouped by type (kept for backward compatibility).
     */
    private function groupByType(Collection $logs, string $field): array
    {
        $result = [];
        foreach ($logs as $log) {
            $key = $log->$field ?? 'unknown';
            $result[$key] = ($result[$key] ?? 0) + 1;
        }

        return $result;
    }

    /**
     * Get posts by status.
     */
    public function getPostsByStatus(Agency $agency): array
    {
        return SocialPost::where('agency_id', $agency->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get cross-platform social analytics.
     */
    public function getCrossPlatformStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:cross_platform", self::CACHE_TTL, function () use ($agency) {
            $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];
            $stats = [];

            foreach ($platforms as $platform) {
                $stats[$platform] = $this->getPlatformStats($agency, $platform);
            }

            return $stats;
        });
    }

    /**
     * Get stats for a specific platform.
     */
    public function getPlatformStats(Agency $agency, string $platform): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('platform', $platform);

        $totalPosts = $posts->count();
        $publishedPosts = (clone $posts)->where('status', 'published')->count();
        $scheduledPosts = (clone $posts)->where('status', 'scheduled')->count();
        $failedPosts = (clone $posts)->where('status', 'failed')->count();
        $draftPosts = (clone $posts)->where('status', 'draft')->count();

        $totalEngagement = (clone $posts)->where('status', 'published')->sum('engagement_rate');
        $avgEngagement = $publishedPosts > 0 ? round($totalEngagement / $publishedPosts, 2) : 0;

        $totalViews = (clone $posts)->where('status', 'published')->sum('views_count');
        $totalLikes = (clone $posts)->where('status', 'published')->sum('likes_count');
        $totalComments = (clone $posts)->where('status', 'published')->sum('comments_count');
        $totalShares = (clone $posts)->where('status', 'published')->sum('shares_count');

        $connectedAccounts = SocialAccount::where('agency_id', $agency->id)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->count();

        return [
            'platform' => $platform,
            'total_posts' => $totalPosts,
            'published' => $publishedPosts,
            'scheduled' => $scheduledPosts,
            'failed' => $failedPosts,
            'draft' => $draftPosts,
            'connected_accounts' => $connectedAccounts,
            'total_engagement' => round($totalEngagement, 2),
            'average_engagement' => $avgEngagement,
            'total_views' => $totalViews,
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
            'total_shares' => $totalShares,
        ];
    }

    /**
     * Get best performing platform by engagement.
     */
    public function getBestPerformingPlatform(Agency $agency): array
    {
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];
        $best = ['platform' => null, 'engagement' => 0];

        foreach ($platforms as $platform) {
            $stats = $this->getPlatformStats($agency, $platform);
            if ($stats['total_engagement'] > $best['engagement']) {
                $best = ['platform' => $platform, 'engagement' => $stats['total_engagement']];
            }
        }

        return $best;
    }

    /**
     * Get social media growth over time.
     */
    public function getSocialGrowth(Agency $agency, int $days = 30): array
    {
        return Cache::remember("analytics:{$agency->id}:growth:{$days}", self::CACHE_TTL, function () use ($agency, $days) {
            $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];
            $growth = [];

            foreach ($platforms as $platform) {
                $daily = SocialPost::where('agency_id', $agency->id)
                    ->where('platform', $platform)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->selectRaw('DATE(created_at) as date, count(*) as count')
                    ->groupBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                $growth[$platform] = $daily;
            }

            return $growth;
        });
    }

    /**
     * Get optimal posting times by platform.
     */
    public function getOptimalPostingTimes(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:optimal_times", self::CACHE_TTL, function () use ($agency) {
            $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];
            $optimal = [];

            foreach ($platforms as $platform) {
                $bestHour = SocialPost::where('agency_id', $agency->id)
                    ->where('platform', $platform)
                    ->where('status', 'published')
                    ->selectRaw("strftime('%H', published_at) as hour, AVG(engagement_rate) as avg_engagement")
                    ->groupBy('hour')
                    ->orderByDesc('avg_engagement')
                    ->value('hour');

                $optimal[$platform] = $bestHour !== null ? sprintf('%02d:00', $bestHour) : 'N/A';
            }

            return $optimal;
        });
    }

    /**
     * Get email campaigns by status.
     */
    public function getEmailCampaignsByStatus(Agency $agency): array
    {
        return EmailCampaign::where('agency_id', $agency->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get total revenue for a date range.
     */
    public function getRevenueForRange(Agency $agency, string $startDate, string $endDate): float
    {
        return (float) Invoice::where('agency_id', $agency->id)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');
    }

    /**
     * Get AI cost for a date range.
     */
    public function getAiCostForRange(Agency $agency, string $startDate, string $endDate): float
    {
        return (float) AiContentLog::where('agency_id', $agency->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('cost_usd');
    }

    /**
     * Get top performing posts (by engagement rate).
     */
    public function getTopPerformingPosts(Agency $agency, int $limit = 10): Collection
    {
        return SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top email campaigns (by open rate).
     */
    public function getTopEmailCampaigns(Agency $agency, int $limit = 10): Collection
    {
        return EmailCampaign::where('agency_id', $agency->id)
            ->where('status', 'sent')
            ->orderByDesc('open_rate')
            ->limit($limit)
            ->get();
    }
}
