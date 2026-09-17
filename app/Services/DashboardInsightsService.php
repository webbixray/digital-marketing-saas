<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialPost;

class DashboardInsightsService
{
    /**
     * Generate AI-powered insights for the dashboard.
     */
    public function generateInsights(Agency $agency): array
    {
        $insights = [];

        // Best posting time
        $bestTime = $this->getBestPostingTime($agency);
        if ($bestTime) {
            $insights[] = [
                'type' => 'timing',
                'icon' => 'clock',
                'title' => 'Optimal Posting Time',
                'message' => "Your audience is most active at {$bestTime}. Schedule posts accordingly.",
                'action' => [
                    'label' => 'View Analytics',
                    'url' => route('analytics.index'),
                ],
            ];
        }

        // Performance trend
        $trend = $this->getPerformanceTrend($agency);
        if ($trend['direction'] === 'up') {
            $insights[] = [
                'type' => 'success',
                'icon' => 'trending-up',
                'title' => 'Engagement Trending Up',
                'message' => "Last 7 days: {$trend['platform']} engagement is up {$trend['percentage']}%.",
                'action' => [
                    'label' => 'View Report',
                    'url' => route('analytics.index'),
                ],
            ];
        }

        // Upcoming scheduled posts
        $scheduledCount = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        if ($scheduledCount > 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'calendar',
                'title' => 'Scheduled Content',
                'message' => "{$scheduledCount} posts are scheduled for the next 7 days.",
                'action' => [
                    'label' => 'Review',
                    'url' => route('social.posts.index', ['status' => 'scheduled']),
                ],
            ];
        }

        // Campaign ending soon
        $endingCampaign = Campaign::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays(5))
            ->first();

        if ($endingCampaign) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'alert',
                'title' => 'Campaign Ending Soon',
                'message' => "Campaign '{$endingCampaign->name}' ends in {$endingCampaign->end_date->diffForHumans()}.",
                'action' => [
                    'label' => 'Review Performance',
                    'url' => route('campaigns.show', $endingCampaign),
                ],
            ];
        }

        // Quota warning
        $quotaService = app(QuotaService::class);
        $quotaStatus = $quotaService->getQuotaStatus($agency);
        foreach ($quotaStatus as $feature => $status) {
            if ($status['unlimited'] || $status['percentage'] < 80) {
                continue;
            }
            $insights[] = [
                'type' => 'warning',
                'icon' => 'exclamation',
                'title' => 'Quota Limit Approaching',
                'message' => "You've used {$status['percentage']}% of your {$feature} quota.",
                'action' => [
                    'label' => 'Upgrade Plan',
                    'url' => route('agency.billing'),
                ],
            ];
            break;
        }

        return $insights;
    }

    /**
     * Analyze historical posts to find the best posting hour.
     */
    private function getBestPostingTime(Agency $agency): ?string
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(30))
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $hourPerformance = [];
        foreach ($posts as $post) {
            $hour = $post->published_at->hour;
            $hourPerformance[$hour] = ($hourPerformance[$hour] ?? 0) + 1;
        }

        arsort($hourPerformance);
        $bestHour = array_key_first($hourPerformance);

        return date('g A', strtotime("{$bestHour}:00"));
    }

    /**
     * Compare last 7 days vs previous 7 days post volume.
     */
    private function getPerformanceTrend(Agency $agency): array
    {
        $lastWeek = SocialPost::where('agency_id', $agency->id)
            ->where('published_at', '>=', now()->subDays(7))
            ->count();

        $previousWeek = SocialPost::where('agency_id', $agency->id)
            ->where('published_at', '>=', now()->subDays(14))
            ->where('published_at', '<', now()->subDays(7))
            ->count();

        if ($previousWeek === 0) {
            return ['direction' => 'neutral', 'percentage' => 0, 'platform' => 'Overall'];
        }

        $change = round((($lastWeek - $previousWeek) / $previousWeek) * 100);

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            'percentage' => abs($change),
            'platform' => 'Overall',
        ];
    }
}
