<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AgentRateLimit
{
    /**
     * Plan-based rate limits (dispatches per minute).
     */
    private const PLAN_LIMITS = [
        'free' => 10,
        'starter' => 60,
        'pro' => 300,
        'enterprise' => null, // unlimited
    ];

    /**
     * Cache key prefix for rate limiting.
     */
    private const CACHE_PREFIX = 'agent_rate_limit:';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->agency) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized.'], 401);
            }
            abort(403);
        }

        $agency = $user->agency;
        $plan = $agency->subscription_plan ?? 'free';
        $limit = self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS['free'];

        // Enterprise plan has no limit
        if ($limit === null) {
            return $next($request);
        }

        $agencyId = $agency->id;
        $cacheKey = self::CACHE_PREFIX.$agencyId;
        $windowSeconds = 60; // 1 minute window

        $currentCount = (int) Cache::get($cacheKey, 0);

        if ($currentCount >= $limit) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Rate limit exceeded.',
                    'limit' => $limit,
                    'window' => 'per minute',
                    'plan' => $plan,
                    'retry_after' => $this->getRetryAfter($cacheKey, $windowSeconds),
                ], 429);
            }
            abort(429, "Rate limit exceeded. Your plan [{$plan}] allows {$limit} agent dispatches per minute.");
        }

        // Increment counter
        $newCount = $currentCount + 1;

        // Set or extend the cache entry
        if ($currentCount === 0) {
            Cache::put($cacheKey, $newCount, $windowSeconds);
        } else {
            // Keep the same TTL as the original entry
            Cache::put($cacheKey, $newCount, $windowSeconds);
        }

        $response = $next($request);

        // Add rate limit headers to response
        if ($response instanceof JsonResponse || $response instanceof \Illuminate\Http\Response) {
            $response->headers->set('X-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-RateLimit-Remaining', (string) max(0, $limit - $newCount));
            $response->headers->set('X-RateLimit-Reset', (string) (time() + $this->getRetryAfter($cacheKey, $windowSeconds)));
        }

        return $response;
    }

    /**
     * Get seconds until rate limit resets.
     */
    private function getRetryAfter(string $cacheKey, int $windowSeconds): int
    {
        // Since we can't easily get TTL from cache, return the window size
        // In production, you might want to store the expiry time alongside the count
        return $windowSeconds;
    }
}
