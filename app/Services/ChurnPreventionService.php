<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChurnPreventionService
{
    /**
     * Get churn risk level for an agency.
     */
    public function getChurnRisk(Agency $agency): string
    {
        $score = 0;

        // Factor 1: No login in 14 days
        $lastActive = $agency->users()->max('last_active_at');
        if (! $lastActive || $lastActive->diffInDays(now()) > 14) {
            $score += 30;
        }

        // Factor 2: No posts in 30 days
        $lastPost = $agency->socialPosts()->max('created_at');
        if (! $lastPost || $lastPost->diffInDays(now()) > 30) {
            $score += 25;
        }

        // Factor 3: Low AI usage (less than 5 generations)
        $aiUsage = $agency->aiContentLogs()->count();
        if ($aiUsage < 5) {
            $score += 20;
        }

        // Factor 4: On free plan with high potential
        if ($agency->subscription_plan === 'free' && $aiUsage > 0) {
            $score += 15;
        }

        // Factor 5: Support tickets or errors
        $recentErrors = $agency->activityLogs()
            ->where('action', 'like', '%error%')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        if ($recentErrors > 3) {
            $score += 10;
        }

        if ($score >= 60) {
            return 'high';
        }
        if ($score >= 30) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get retention offer for churning user.
     */
    public function getRetentionOffer(Agency $agency): array
    {
        $risk = $this->getChurnRisk($agency);

        return match ($risk) {
            'high' => [
                'discount_percent' => 50,
                'message' => 'We\'d love to keep you! Here\'s 50% off your next 3 months.',
                'urgency' => 'high',
            ],
            'medium' => [
                'discount_percent' => 25,
                'message' => 'Before you go, here\'s 25% off your next month.',
                'urgency' => 'medium',
            ],
            default => [
                'discount_percent' => 10,
                'message' => 'Here\'s a special 10% discount to stay with us.',
                'urgency' => 'low',
            ],
        };
    }

    /**
     * Get dormant users for re-engagement campaign.
     */
    public function getDormantUsers(int $daysInactive = 14): array
    {
        $cutoff = now()->subDays($daysInactive);

        return User::where('last_active_at', '<', $cutoff)
            ->orWhereNull('last_active_at')
            ->with('agency')
            ->get()
            ->filter(fn ($u) => $u->agency && $u->agency->subscription_plan !== 'free')
            ->values()
            ->toArray();
    }

    /**
     * Get upgrade prompt based on usage.
     */
    public function getUpgradePrompt(Agency $agency): ?array
    {
        $plan = $agency->subscription_plan;
        $quotaService = app(QuotaService::class);

        // Check if user is near their limits
        $postsPercentage = $quotaService->usagePercentage($agency, 'posts');
        $aiPercentage = $quotaService->usagePercentage($agency, 'ai_generations');

        if ($postsPercentage >= 90 || $aiPercentage >= 90) {
            return [
                'type' => 'limit_warning',
                'message' => 'You\'re almost out of your monthly allowance. Upgrade now to keep posting!',
                'urgency' => 'high',
            ];
        }

        if ($postsPercentage >= 75 || $aiPercentage >= 75) {
            return [
                'type' => 'usage_warning',
                'message' => 'You\'ve used over 75% of your monthly allowance.',
                'urgency' => 'medium',
            ];
        }

        // Check if free user is active
        if ($plan === 'free') {
            $postsCount = $agency->socialPosts()->count();
            if ($postsCount >= 5) {
                return [
                    'type' => 'upgrade_prompt',
                    'message' => 'You\'ve created '.$postsCount.' posts! Unlock unlimited posts and AI features.',
                    'urgency' => 'medium',
                ];
            }
        }

        return null;
    }

    /**
     * Record cancellation survey response.
     */
    public function recordSurveyResponse(int $agencyId, string $reason, ?string $feedback = null): void
    {
        Log::info('Cancellation survey', [
            'agency_id' => $agencyId,
            'reason' => $reason,
            'feedback' => $feedback,
        ]);

        // Store in cache for analytics
        $cacheKey = 'churn_survey:'.date('Y-m');
        $data = Cache::get($cacheKey, []);
        $data[] = [
            'agency_id' => $agencyId,
            'reason' => $reason,
            'feedback' => $feedback,
            'date' => now()->toDateString(),
        ];
        Cache::put($cacheKey, $data, now()->addDays(90));
    }

    /**
     * Get churn analytics.
     */
    public function getChurnAnalytics(int $days = 30): array
    {
        $cacheKey = 'churn_survey:'.date('Y-m');
        $surveys = Cache::get($cacheKey, []);

        $reasons = array_count_values(array_column($surveys, 'reason'));
        arsort($reasons);

        return [
            'total_surveys' => count($surveys),
            'reasons' => $reasons,
            'period_days' => $days,
        ];
    }
}
