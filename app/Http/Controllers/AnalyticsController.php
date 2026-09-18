<?php

namespace App\Http\Controllers;

use App\Models\AiContentLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;
        $agencyId = $agency->id;
        $range = $request->get('range', '30');
        $startDate = Carbon::now()->subDays($range);

        // Use cache for expensive analytics queries
        $cacheKey = "analytics:page:{$agencyId}:{$range}";
        $data = Cache::remember($cacheKey, 300, function () use ($agencyId, $startDate) {
            // Optimized post stats with single query
            $postAgg = SocialPost::where('agency_id', $agencyId)
                ->selectRaw('
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published_posts,
                    SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled_posts,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_posts,
                    AVG(quality_score) as avg_quality_score
                ')
                ->first();

            $postStats = [
                'total_posts' => (int) $postAgg->total_posts,
                'published_posts' => (int) $postAgg->published_posts,
                'scheduled_posts' => (int) $postAgg->scheduled_posts,
                'failed_posts' => (int) $postAgg->failed_posts,
                'avg_quality_score' => round((float) ($postAgg->avg_quality_score ?? 0), 1),
            ];

            $engagement = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->selectRaw('
                    COALESCE(SUM(views_count), 0) as total_views,
                    COALESCE(SUM(likes_count), 0) as total_likes,
                    COALESCE(SUM(comments_count), 0) as total_comments,
                    COALESCE(SUM(shares_count), 0) as total_shares,
                    COALESCE(SUM(clicks_count), 0) as total_clicks
                ')
                ->first();

            $platformStats = SocialPost::where('agency_id', $agencyId)
                ->select('platform', DB::raw('count(*) as total'))
                ->groupBy('platform')
                ->get()
                ->keyBy('platform');

            // Optimized campaign stats
            $campaignAgg = Campaign::where('agency_id', $agencyId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
                ')
                ->first();

            $campaignStats = [
                'total' => (int) $campaignAgg->total,
                'active' => (int) $campaignAgg->active,
                'completed' => (int) $campaignAgg->completed,
            ];

            // Optimized client stats
            $clientAgg = Client::where('agency_id', $agencyId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "lead" THEN 1 ELSE 0 END) as leads
                ')
                ->first();

            $clientStats = [
                'total' => (int) $clientAgg->total,
                'active' => (int) $clientAgg->active,
                'leads' => (int) $clientAgg->leads,
            ];

            // Optimized AI stats
            $aiAgg = AiContentLog::where('agency_id', $agencyId)
                ->selectRaw('
                    COUNT(*) as total_generations,
                    SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful,
                    COALESCE(SUM(cost_usd), 0) as total_cost,
                    COALESCE(SUM(total_tokens), 0) as total_tokens
                ')
                ->first();

            $aiStats = [
                'total_generations' => (int) $aiAgg->total_generations,
                'successful' => (int) $aiAgg->successful,
                'total_cost' => (float) $aiAgg->total_cost,
                'total_tokens' => (int) $aiAgg->total_tokens,
            ];

            // Optimized revenue stats
            $revenueAgg = Invoice::where('agency_id', $agencyId)
                ->selectRaw('
                    COALESCE(SUM(total), 0) as total,
                    COALESCE(SUM(CASE WHEN status = "paid" THEN total ELSE 0 END), 0) as paid,
                    COALESCE(SUM(CASE WHEN status = "pending" THEN total ELSE 0 END), 0) as pending
                ')
                ->first();

            $revenueStats = [
                'total' => (float) $revenueAgg->total,
                'paid' => (float) $revenueAgg->paid,
                'pending' => (float) $revenueAgg->pending,
            ];

            $dailyEngagement = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->where('published_at', '>=', $startDate)
                ->selectRaw('
                    DATE(published_at) as date,
                    COALESCE(SUM(likes_count + comments_count + shares_count), 0) as engagement
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $bestPosts = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->orderBy('likes_count', 'desc')
                ->limit(5)
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

            return compact('postStats', 'engagement', 'platformStats', 'campaignStats', 'clientStats', 'aiStats', 'revenueStats', 'dailyEngagement', 'bestPosts');
        });

        return view('analytics.index', array_merge(['agency' => $agency, 'range' => $range], $data));
    }
}
