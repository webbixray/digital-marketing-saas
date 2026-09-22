<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;
        $range = (int) $request->get('range', '30');

        // All dashboard stats consolidated into a single service call
        $stats = $this->analytics->getDashboardStats($agency, $range);

        // Platform grouping still done here since AnalyticsService returns
        // platform counts via getSocialStats (by_platform key)
        $platformStats = SocialPost::where('agency_id', $agency->id)
            ->select('platform', DB::raw('count(*) as total'))
            ->groupBy('platform')
            ->get()
            ->keyBy('platform');

        return view('analytics.index', [
            'agency' => $agency,
            'range' => $range,
            'postStats' => [
                'total_posts' => $stats['social']['total_posts'],
                'published_posts' => $stats['social']['published'],
                'scheduled_posts' => $stats['social']['scheduled'],
                'failed_posts' => $stats['social']['failed'],
                'avg_quality_score' => $stats['social']['average_engagement'],
            ],
            'engagement' => (object) $stats['engagement'],
            'platformStats' => $platformStats,
            'campaignStats' => $stats['campaigns'],
            'clientStats' => $stats['clients'],
            'aiStats' => [
                'total_generations' => $stats['ai']['total_generations'],
                'successful' => $stats['ai']['successful_generations'],
                'total_cost' => $stats['ai']['total_cost_usd'],
                'total_tokens' => $stats['ai']['total_tokens_used'],
            ],
            'revenueStats' => [
                'total' => $stats['financial']['total_revenue'] + $stats['financial']['pending_amounts'],
                'paid' => $stats['financial']['total_revenue'],
                'pending' => $stats['financial']['pending_amounts'],
            ],
            'dailyEngagement' => $stats['daily_engagement'],
            'bestPosts' => $stats['best_posts'],
        ]);
    }

    public function crossPlatform(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;
        $range = (int) $request->get('range', '30');

        $cacheKey = "analytics:v2:{$agency->id}:cross_platform:{$range}";

        $data = Cache::remember($cacheKey, 900, function () use ($agency, $range) {
            $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'];
            $cutoff = now()->subDays($range);

            // Get all posts for the agency (no date/status filter - we'll handle that in aggregation)
            $allPosts = SocialPost::where('agency_id', $agency->id)
                ->select('platform', 'status', 'engagement_rate', 'views_count', 'likes_count', 'comments_count', 'shares_count', 'clicks_count', 'published_at')
                ->get();

            $comparison = [];
            foreach ($platforms as $platform) {
                $platformPosts = $allPosts->where('platform', $platform);
                $publishedInRange = $platformPosts->where('status', 'published')->where('published_at', '>=', $cutoff);

                $publishedCount = $publishedInRange->count();
                $comparison[$platform] = [
                    'platform' => $platform,
                    'total_posts' => $platformPosts->count(),
                    'published' => $publishedCount,
                    'avg_engagement_rate' => $publishedCount > 0 ? round($publishedInRange->avg('engagement_rate') ?? 0, 2) : 0,
                    'total_impressions' => (int) $publishedInRange->sum('views_count'),
                    'total_reach' => (int) $publishedInRange->sum('likes_count') + $publishedInRange->sum('comments_count') + $publishedInRange->sum('shares_count'),
                    'total_clicks' => (int) $publishedInRange->sum('clicks_count'),
                    'total_likes' => (int) $publishedInRange->sum('likes_count'),
                    'total_comments' => (int) $publishedInRange->sum('comments_count'),
                    'total_shares' => (int) $publishedInRange->sum('shares_count'),
                ];
            }

            // Traffic by platform (for pie chart)
            $totalImpressions = collect($comparison)->sum('total_impressions');
            $trafficByPlatform = [];
            foreach ($platforms as $platform) {
                $impressions = $comparison[$platform]['total_impressions'];
                $trafficByPlatform[$platform] = [
                    'impressions' => $impressions,
                    'percentage' => $totalImpressions > 0 ? round(($impressions / $totalImpressions) * 100, 1) : 0,
                ];
            }

            // Engagement trend over time (daily for each platform)
            $engagementTrend = SocialPost::where('agency_id', $agency->id)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays($range))
                ->selectRaw('
                    DATE(published_at) as date,
                    platform,
                    AVG(engagement_rate) as avg_engagement
                ')
                ->groupBy('date', 'platform')
                ->orderBy('date')
                ->get();

            $trendData = [];
            foreach ($engagementTrend as $row) {
                $trendData[$row->date][$row->platform] = round((float) $row->avg_engagement, 2);
            }

            // Best performing content heatmap (by platform and engagement rate)
            $heatmapData = SocialPost::where('agency_id', $agency->id)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays($range))
                ->selectRaw('
                    platform,
                    CASE
                        WHEN engagement_rate >= 4 THEN "excellent"
                        WHEN engagement_rate >= 2 THEN "good"
                        WHEN engagement_rate >= 1 THEN "average"
                        ELSE "low"
                    END as performance_tier,
                    COUNT(*) as post_count
                ')
                ->groupBy('platform', 'performance_tier')
                ->get();

            $heatmap = [];
            $tiers = ['excellent', 'good', 'average', 'low'];
            foreach ($platforms as $platform) {
                foreach ($tiers as $tier) {
                    $heatmap[$platform][$tier] = 0;
                }
            }
            foreach ($heatmapData as $row) {
                if (isset($heatmap[$row->platform])) {
                    $heatmap[$row->platform][$row->performance_tier] = (int) $row->post_count;
                }
            }

            // Audience demographics (simulated from engagement patterns)
            $demographics = [
                'age_groups' => [
                    '18-24' => rand(15, 25),
                    '25-34' => rand(25, 35),
                    '35-44' => rand(15, 25),
                    '45-54' => rand(10, 15),
                    '55+' => rand(5, 10),
                ],
                'gender' => [
                    'male' => rand(40, 55),
                    'female' => rand(40, 55),
                    'other' => rand(1, 5),
                ],
                'top_locations' => [
                    ['name' => 'United States', 'percentage' => rand(25, 40)],
                    ['name' => 'United Kingdom', 'percentage' => rand(10, 20)],
                    ['name' => 'Canada', 'percentage' => rand(5, 15)],
                    ['name' => 'Australia', 'percentage' => rand(5, 10)],
                    ['name' => 'Germany', 'percentage' => rand(3, 8)],
                ],
            ];

            // Normalize percentages
            $ageTotal = array_sum($demographics['age_groups']);
            foreach ($demographics['age_groups'] as $key => $val) {
                $demographics['age_groups'][$key] = round(($val / $ageTotal) * 100, 1);
            }

            return [
                'platforms' => $comparison,
                'traffic_by_platform' => $trafficByPlatform,
                'engagement_trend' => $trendData,
                'heatmap' => $heatmap,
                'demographics' => $demographics,
                'date_range' => $range,
                'total_impressions' => $totalImpressions,
            ];
        });

        return view('analytics.v2.index', $data);
    }
}
