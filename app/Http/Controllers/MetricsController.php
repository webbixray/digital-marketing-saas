<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    /**
     * Get key SaaS metrics (admin only)
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'saas_metrics:'.now()->format('Y-m-d');

        Log::info('Metrics dashboard viewed', ['agency_id' => $request->user()->agency_id]);

        return response()->json(Cache::remember($cacheKey, 3600, function () {
            return [
                'users' => $this->getUserMetrics(),
                'revenue' => $this->getRevenueMetrics(),
                'engagement' => $this->getEngagementMetrics(),
                'conversion' => $this->getConversionMetrics(),
            ];
        }));
    }

    private function getUserMetrics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('last_active_at', '>=', now()->subDays(30))->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)->count();

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'new_this_month' => $newUsersThisMonth,
            'activation_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
        ];
    }

    private function getRevenueMetrics(): array
    {
        $plans = Agency::select('subscription_plan', DB::raw('count(*) as count'))
            ->groupBy('subscription_plan')
            ->pluck('count', 'subscription_plan');

        $mrr = $this->calculateMRR($plans);

        return [
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'plan_distribution' => $plans,
            'arpu' => $plans->sum() > 0 ? round($mrr / $plans->sum(), 2) : 0,
        ];
    }

    private function calculateMRR($plans): float
    {
        $prices = [
            'free' => 0,
            'starter' => 19,
            'pro' => 49,
            'agency' => 99,
            'enterprise' => 299,
        ];

        $mrr = 0;
        foreach ($plans as $plan => $count) {
            $mrr += ($prices[$plan] ?? 0) * $count;
        }

        return $mrr;
    }

    private function getEngagementMetrics(): array
    {
        return [
            'total_posts' => SocialPost::count(),
            'posts_this_month' => SocialPost::whereMonth('created_at', now()->month)->count(),
            'total_campaigns' => Campaign::count(),
            'active_workflows' => Workflow::where('status', 'active')->count(),
        ];
    }

    private function getConversionMetrics(): array
    {
        $freeUsers = Agency::where('subscription_plan', 'free')->count();
        $paidUsers = Agency::where('subscription_plan', '!=', 'free')->count();
        $total = $freeUsers + $paidUsers;

        return [
            'free_to_paid_rate' => $total > 0 ? round(($paidUsers / $total) * 100, 2) : 0,
            'free_users' => $freeUsers,
            'paid_users' => $paidUsers,
            'trial_conversion_rate' => 0,
        ];
    }
}
