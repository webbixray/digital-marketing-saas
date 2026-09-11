<?php

namespace App\Services\Social;

use App\Models\Agency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlatformRateLimitService
{
    /**
     * Platform rate limit configurations.
     * Limits are per-hour unless otherwise specified.
     */
    private const PLATFORM_LIMITS = [
        'facebook' => [
            'posts_per_hour' => 25,
            'api_calls_per_hour' => 200,
        ],
        'instagram' => [
            'posts_per_hour' => 20,
            'api_calls_per_hour' => 100,
        ],
        'twitter' => [
            'posts_per_hour' => 30,
            'api_calls_per_hour' => 150,
        ],
        'linkedin' => [
            'posts_per_hour' => 15,
            'api_calls_per_hour' => 100,
        ],
        'tiktok' => [
            'posts_per_hour' => 10,
            'api_calls_per_hour' => 50,
        ],
        'pinterest' => [
            'posts_per_hour' => 20,
            'api_calls_per_hour' => 100,
        ],
    ];

    /**
     * Plan multipliers for rate limits.
     */
    private const PLAN_MULTIPLIERS = [
        'free' => 1,
        'starter' => 2,
        'pro' => 5,
        'enterprise' => 10,
    ];

    /**
     * Check if a platform action is allowed.
     */
    public function isAllowed(int $agencyId, string $platform, string $action = 'posts'): bool
    {
        $limit = $this->getLimit($agencyId, $platform, $action);
        $current = $this->getCurrentUsage($agencyId, $platform, $action);

        return $current < $limit;
    }

    /**
     * Get the rate limit for a platform action.
     */
    public function getLimit(int $agencyId, string $platform, string $action = 'posts'): int
    {
        $agency = Agency::find($agencyId);
        $plan = $agency?->subscription_plan ?? 'free';
        $multiplier = self::PLAN_MULTIPLIERS[$plan] ?? 1;

        $platformLimits = self::PLATFORM_LIMITS[$platform] ?? [
            'posts_per_hour' => 10,
            'api_calls_per_hour' => 50,
        ];

        $baseLimit = $platformLimits["{$action}_per_hour"] ?? 10;

        return $baseLimit * $multiplier;
    }

    /**
     * Get current usage count for a platform action.
     */
    public function getCurrentUsage(int $agencyId, string $platform, string $action = 'posts'): int
    {
        $cacheKey = $this->getCacheKey($agencyId, $platform, $action);
        return Cache::get($cacheKey, 0);
    }

    /**
     * Record a platform action.
     */
    public function recordAction(int $agencyId, string $platform, string $action = 'posts', int $count = 1): void
    {
        $cacheKey = $this->getCacheKey($agencyId, $platform, $action);
        $current = Cache::get($cacheKey, 0);
        $newCount = $current + $count;

        // Store with 1-hour expiration
        Cache::put($cacheKey, $newCount, 3600);

        Log::debug("Rate limit recorded", [
            'agency_id' => $agencyId,
            'platform' => $platform,
            'action' => $action,
            'count' => $newCount,
            'limit' => $this->getLimit($agencyId, $platform, $action),
        ]);
    }

    /**
     * Get remaining allowance for a platform action.
     */
    public function getRemaining(int $agencyId, string $platform, string $action = 'posts'): int
    {
        $limit = $this->getLimit($agencyId, $platform, $action);
        $current = $this->getCurrentUsage($agencyId, $platform, $action);

        return max(0, $limit - $current);
    }

    /**
     * Get seconds until rate limit resets.
     */
    public function getSecondsUntilReset(int $agencyId, string $platform, string $action = 'posts'): int
    {
        $cacheKey = $this->getCacheKey($agencyId, $platform, $action);

        // Since Laravel Cache doesn't expose TTL directly, we track it separately
        $ttlKey = $cacheKey . ':ttl';
        $expiresAt = Cache::get($ttlKey);

        if (!$expiresAt) {
            return 0;
        }

        return max(0, $expiresAt - time());
    }

    /**
     * Reset rate limits for an agency.
     */
    public function resetLimits(int $agencyId): void
    {
        foreach (array_keys(self::PLATFORM_LIMITS) as $platform) {
            foreach (['posts', 'api_calls'] as $action) {
                $cacheKey = $this->getCacheKey($agencyId, $platform, $action);
                Cache::forget($cacheKey);
                Cache::forget($cacheKey . ':ttl');
            }
        }

        Log::info("Rate limits reset for agency {$agencyId}");
    }

    /**
     * Get cache key for rate limiting.
     */
    private function getCacheKey(int $agencyId, string $platform, string $action): string
    {
        $hour = now()->format('YmdH');
        return "rate_limit:{$agencyId}:{$platform}:{$action}:{$hour}";
    }
}
