<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
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
}
