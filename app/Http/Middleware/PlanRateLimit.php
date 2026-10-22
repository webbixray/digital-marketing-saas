<?php

namespace App\Http\Middleware;

use App\Models\Agency;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plan-based API rate limiting middleware.
 *
 * Applies different rate limits based on the agency's subscription plan:
 * - Free: 60 requests/minute
 * - Starter: 300 requests/minute
 * - Pro: 1,200 requests/minute
 * - Agency: 3,600 requests/minute
 * - Enterprise: unlimited
 *
 * Falls back to IP-based limiting for unauthenticated requests.
 */
class PlanRateLimit
{
    /**
     * Plan-based limits (requests per minute).
     *
     * null = unlimited.
     */
    private const PLAN_LIMITS = [
        'free' => 60,
        'starter' => 300,
        'pro' => 1200,
        'agency' => 3600,
        'enterprise' => null,
    ];

    private const CACHE_PREFIX = 'api_plan_rate_limit:';

    private const WINDOW_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Unauthenticated requests: IP-based fallback
        if (! $user || ! $user->agency_id) {
            return $this->applyIpLimit($request, $next);
        }

        $agency = $user->agency;

        $plan = $agency->subscription_plan ?? 'free';
        $limit = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS['free'];

        // Enterprise plan: no rate limiting
        if ($plan === 'enterprise') {
            return $next($request);
        }

        /** @var int $limit */
        $agencyId = $agency->id;
        $cacheKey = self::CACHE_PREFIX . $agencyId;

        $currentCount = (int) Cache::get($cacheKey, 0);

        if ($currentCount >= $limit) {
            $retryAfter = $this->getRetryAfter($cacheKey, self::WINDOW_SECONDS);

            $response = response()->json([
                'success' => false,
                'message' => 'API rate limit exceeded. Upgrade your plan for higher limits.',
                'error' => 'rate_limit_exceeded',
                'limit' => $limit,
                'plan' => $plan,
                'retry_after' => $retryAfter,
                'window' => 'per minute',
            ], 429);

            $response->headers->set('X-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Plan', $plan);
            $response->headers->set('Retry-After', (string) $retryAfter);

            return $response;
        }

        // Increment counter
        $newCount = $currentCount + 1;

        if ($currentCount === 0) {
            Cache::put($cacheKey, $newCount, self::WINDOW_SECONDS);
        } else {
            Cache::put($cacheKey, $newCount, self::WINDOW_SECONDS);
        }

        $response = $next($request);

        $remaining = max(0, $limit - $newCount);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Plan', $plan);
        $response->headers->set('X-RateLimit-Reset', (string) (time() + $this->getRetryAfter($cacheKey, self::WINDOW_SECONDS)));

        return $response;
    }

    /**
     * Apply IP-based rate limiting for unauthenticated requests.
     */
    private function applyIpLimit(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?? 'anonymous';
        $cacheKey = self::CACHE_PREFIX . 'ip:' . $ip;
        $limit = 20; // Stricter limit for unauthenticated

        $currentCount = (int) Cache::get($cacheKey, 0);

        if ($currentCount >= $limit) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please authenticate for higher limits.',
                'error' => 'rate_limit_exceeded',
                'limit' => $limit,
                'retry_after' => $this->getRetryAfter($cacheKey, self::WINDOW_SECONDS),
            ], 429);
        }

        $newCount = $currentCount + 1;

        if ($currentCount === 0) {
            Cache::put($cacheKey, $newCount, self::WINDOW_SECONDS);
        } else {
            Cache::put($cacheKey, $newCount, self::WINDOW_SECONDS);
        }

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $limit - $newCount));

        return $response;
    }

    private function getRetryAfter(string $cacheKey, int $windowSeconds): int
    {
        return $windowSeconds;
    }
}
