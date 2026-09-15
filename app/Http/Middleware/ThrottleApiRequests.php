<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiRequests
{
    /**
     * Handle an incoming request with per-user rate limiting.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decaySeconds = 60): Response
    {
        $user = $request->user();

        if ($user) {
            $key = 'api:' . $user->id;
        } elseif ($request->ip()) {
            $key = 'api:ip:' . $request->ip();
        } else {
            $key = 'api:anonymous';
        }

        $limiter = app(RateLimiter::class);

        // Check if too many attempts
        if ($limiter->attempts($key) >= $maxAttempts) {
            $retryAfter = $limiter->availableIn($key);
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'error' => 'rate_limit_exceeded',
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Record the hit
        $limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
