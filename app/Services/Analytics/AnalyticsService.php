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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Return all core dashboard stats for an agency.
     *
     * @param Agency $agency
     * @param int $days Number of days for date-range queries (default 30)
     * @return array
     */
    public function getDashboardStats(Agency $agency, int $days = 30): array
    {
        return Cache::remember("analytics:{$agency->id}:dashboard:{$days}", self::CACHE_TTL, function () use ($agency, $days) {
            return [
                'overview' => $this->getOverviewStats($agency),
                'social' => $this->getSocialStats($agency),
                'email' => $this->getEmailStats($agency),
                'financial' => $this->getFinancialStats($agency),
                'ai' => $this->getAiStats($agency),
                'engagement' => $this->getEngagementStats($agency),
                'campaigns' => $this->getCampaignStats($agency),
                'clients' => $this->getClientStats($agency),
                'daily_engagement' => $this->getDailyEngagement($agency, $days),
                'best_posts' => $this->getBestPosts($agency),
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
                    ->where('is_active', true)->count(),
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
     * Engagement stats - single query for all engagement metrics.
     */
    public function getEngagementStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:engagement", self::CACHE_TTL, function () use ($agency) {
            $stats = SocialPost::where('agency_id', $agency->id)
                ->where('status', 'published')
                ->selectRaw('
                    COALESCE(SUM(views_count), 0) as total_views,
                    COALESCE(SUM(likes_count), 0) as total_likes,
                    COALESCE(SUM(comments_count), 0) as total_comments,
                    COALESCE(SUM(shares_count), 0) as total_shares,
                    COALESCE(SUM(clicks_count), 0) as total_clicks
                ')
                ->first();

            return [
                'total_views' => (int) $stats->total_views,
                'total_likes' => (int) $stats->total_likes,
                'total_comments' => (int) $stats->total_comments,
                'total_shares' => (int) $stats->total_shares,
                'total_clicks' => (int) $stats->total_clicks,
            ];
        });
    }

    /**
     * Campaign stats - single query for all campaign metrics.
     */
    public function getCampaignStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:campaigns", self::CACHE_TTL, function () use ($agency) {
            $stats = Campaign::where('agency_id', $agency->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
                ')
                ->first();

            return [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active,
                'completed' => (int) $stats->completed,
            ];
        });
    }

    /**
     * Client stats - single query for all client metrics.
     */
    public function getClientStats(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:clients", self::CACHE_TTL, function () use ($agency) {
            $stats = Client::where('agency_id', $agency->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "lead" THEN 1 ELSE 0 END) as leads
                ')
                ->first();

            return [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active,
                'leads' => (int) $stats->leads,
            ];
        });
    }

    /**
     * Daily engagement over time - single query.
     */
    public function getDailyEngagement(Agency $agency, int $days = 30): array
    {
        return Cache::remember("analytics:{$agency->id}:daily_engagement:{$days}", self::CACHE_TTL, function () use ($agency, $days) {
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
     * Best performing posts - single query.
     */
    public function getBestPosts(Agency $agency, int $limit = 5): array
    {
        return Cache::remember("analytics:{$agency->id}:best_posts:{$limit}", self::CACHE_TTL, function () use ($agency, $limit) {
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
        Cache::forget("analytics:{$agency->id}:engagement");
        Cache::forget("analytics:{$agency->id}:campaigns");
        Cache::forget("analytics:{$agency->id}:clients");
        Cache::forget("analytics:{$agency->id}:daily_engagement");
        Cache::forget("analytics:{$agency->id}:best_posts");
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
    public function getBestPlatform(Agency $agency): array
    {
        return Cache::remember("analytics:{$agency->id}:best_platform", self::CACHE_TTL, function () use ($agency) {
            $platforms = SocialPost::where('agency_id', $agency->id)
                ->selectRaw('platform, SUM(engagement_rate) as total_engagement, COUNT(*) as post_count')
                ->groupBy('platform')
                ->orderByDesc('total_engagement')
                ->get()
                ->map(function ($item) {
                    return [
                        'platform' => $item->platform,
                        'total_engagement' => round((float) $item->total_engagement, 2),
                        'post_count' => (int) $item->post_count,
                    ];
                })
                ->toArray();

            // Return the top platform
            return $platforms[0] ?? ['platform' => null, 'total_engagement' => 0, 'post_count' => 0];
        });
    }

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
            // Single query for all platforms
            $platformStats = SocialPost::where('agency_id', $agency->id)
                ->selectRaw('
                    platform,
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = "published" THEN engagement_rate ELSE 0 END) as total_engagement,
                    AVG(CASE WHEN status = "published" THEN engagement_rate ELSE NULL END) as average_engagement,
                    SUM(CASE WHEN status = "published" THEN views_count ELSE 0 END) as total_views,
                    SUM(CASE WHEN status = "published" THEN likes_count ELSE 0 END) as total_likes,
                    SUM(CASE WHEN status = "published" THEN comments_count ELSE 0 END) as total_comments,
                    SUM(CASE WHEN status = "published" THEN shares_count ELSE 0 END) as total_shares
                ')
                ->groupBy('platform')
                ->get()
                ->keyBy('platform');

            // Single query for connected accounts
            $connectedAccounts = SocialAccount::where('agency_id', $agency->id)
                ->where('is_active', true)
                ->selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform');

            $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];
            $stats = [];

            foreach ($platforms as $platform) {
                $ps = $platformStats[$platform] ?? null;
                $published = (int) ($ps?->published ?? 0);
                $totalEngagement = (float) ($ps?->total_engagement ?? 0);

                $stats[$platform] = [
                    'platform' => $platform,
                    'total_posts' => (int) ($ps?->total_posts ?? 0),
                    'published' => $published,
                    'scheduled' => (int) ($ps?->scheduled ?? 0),
                    'failed' => (int) ($ps?->failed ?? 0),
                    'draft' => (int) ($ps?->draft ?? 0),
                    'connected_accounts' => (int) ($connectedAccounts[$platform] ?? 0),
                    'total_engagement' => round($totalEngagement, 2),
                    'average_engagement' => $published > 0 ? round($totalEngagement / $published, 2) : 0,
                    'total_views' => (int) ($ps?->total_views ?? 0),
                    'total_likes' => (int) ($ps?->total_likes ?? 0),
                    'total_comments' => (int) ($ps?->total_comments ?? 0),
                    'total_shares' => (int) ($ps?->total_shares ?? 0),
                ];
            }

            return $stats;
        });
    }

    /**
     * Get stats for a specific platform - OPTIMIZED: single aggregation instead of 9 clone queries.
     */
    public function getPlatformStats(Agency $agency, string $platform): array
    {
        $stats = SocialPost::where('agency_id', $agency->id)
            ->where('platform', $platform)
            ->selectRaw('
                COUNT(*) as total_posts,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = "published" THEN engagement_rate ELSE 0 END) as total_engagement,
                AVG(CASE WHEN status = "published" THEN engagement_rate ELSE NULL END) as avg_engagement,
                SUM(CASE WHEN status = "published" THEN views_count ELSE 0 END) as total_views,
                SUM(CASE WHEN status = "published" THEN likes_count ELSE 0 END) as total_likes,
                SUM(CASE WHEN status = "published" THEN comments_count ELSE 0 END) as total_comments,
                SUM(CASE WHEN status = "published" THEN shares_count ELSE 0 END) as total_shares
            ')
            ->first();

        $published = (int) $stats->published;
        $totalEngagement = (float) ($stats->total_engagement ?? 0);

        $connectedAccounts = SocialAccount::where('agency_id', $agency->id)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->count();

        return [
            'platform' => $platform,
            'total_posts' => (int) $stats->total_posts,
            'published' => $published,
            'scheduled' => (int) $stats->scheduled,
            'failed' => (int) $stats->failed,
            'draft' => (int) $stats->draft,
            'connected_accounts' => $connectedAccounts,
            'total_engagement' => round($totalEngagement, 2),
            'average_engagement' => $published > 0 ? round($totalEngagement / $published, 2) : 0,
            'total_views' => (int) ($stats->total_views ?? 0),
            'total_likes' => (int) ($stats->total_likes ?? 0),
            'total_comments' => (int) ($stats->total_comments ?? 0),
            'total_shares' => (int) ($stats->total_shares ?? 0),
        ];
    }

    /**
     * Get best performing platform by engagement.
     */
    public function getBestPerformingPlatform(Agency $agency): array
    {
        $result = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->selectRaw('platform, SUM(engagement_rate) as total_engagement')
            ->groupBy('platform')
            ->orderByDesc('total_engagement')
            ->first();

        return [
            'platform' => $result?->platform,
            'engagement' => (float) ($result?->total_engagement ?? 0),
        ];
    }

    /**
     * Get social media growth over time.
     */
    public function getSocialGrowth(Agency $agency, int $days = 30): array
    {
        return Cache::remember("analytics:{$agency->id}:growth:{$days}", self::CACHE_TTL, function () use ($agency, $days) {
            // Single query for all platforms
            $daily = SocialPost::where('agency_id', $agency->id)
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('platform, DATE(created_at) as date, count(*) as count')
                ->groupBy('platform', 'date')
                ->get();

            $growth = [];
            foreach ($daily as $row) {
                $growth[$row->platform][$row->date] = (int) $row->count;
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
            // Single query for all platforms
            $hours = SocialPost::where('agency_id', $agency->id)
                ->where('status', 'published')
                ->selectRaw("platform, strftime('%H', published_at) as hour, AVG(engagement_rate) as avg_engagement")
                ->groupBy('platform', 'hour')
                ->orderBy('platform')
                ->orderByDesc('avg_engagement')
                ->get();

            $optimal = [];
            foreach ($hours as $row) {
                if (! isset($optimal[$row->platform])) {
                    $optimal[$row->platform] = sprintf('%02d:00', $row->hour);
                }
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

    /**
     * Generate a client-facing report for a specific date range.
     */
    public function generateClientReport(Agency $agency, Client $client, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('client_id', $client->id)
            ->whereBetween('published_at', [$start, $end])
            ->get();

        $campaigns = Campaign::where('agency_id', $agency->id)
            ->where('client_id', $client->id)
            ->whereBetween('start_date', [$start, $end])
            ->get();

        // Calculate engagement from individual metrics
        $totalEngagement = $posts->sum(function ($post) {
            return ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->shares_count ?? 0);
        });
        $totalImpressions = $posts->sum('views_count');
        $avgEngagementRate = $totalImpressions > 0
            ? round(($totalEngagement / $totalImpressions) * 100, 2)
            : 0;

        $platformStats = $posts->groupBy('platform')->map(function ($platformPosts) {
            $engagement = $platformPosts->sum(function ($post) {
                return ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->shares_count ?? 0);
            });

            return [
                'posts_count' => $platformPosts->count(),
                'total_engagement' => $engagement,
                'total_impressions' => $impressions,
                'avg_engagement_rate' => $impressions > 0
                    ? round(($engagement / $impressions) * 100, 2)
                    : 0,
            ];
        });

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $start->diffInDays($end) + 1,
            ],
            'summary' => [
                'total_posts' => $posts->count(),
                'total_campaigns' => $campaigns->count(),
                'total_engagement' => $totalEngagement,
                'total_impressions' => $totalImpressions,
                'avg_engagement_rate' => $avgEngagementRate,
            ],
            'platform_breakdown' => $platformStats,
            'top_posts' => $posts->sortByDesc('engagement_rate')->take(5)->map(function ($post) {
                return [
                    'id' => $post->id,
                    'platform' => $post->platform,
                    'content' => Str::limit($post->content, 100),
                    'engagement_rate' => $post->engagement_rate,
                    'views_count' => $post->views_count,
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->comments_count,
                    'shares_count' => $post->shares_count,
                    'published_at' => $post->published_at?->toDateString(),
                ];
            })->values(),
            'campaigns' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'posts_count' => $campaign->posts_count,
                ];
            }),
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
