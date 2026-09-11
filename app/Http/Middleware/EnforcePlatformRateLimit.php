<?php

namespace App\Http\Middleware;

use App\Services\Social\PlatformRateLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlatformRateLimit
{
    public function __construct(
        private readonly PlatformRateLimitService $rateLimitService,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $platform = 'twitter', string $action = 'posts'): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $agencyId = $user->agency_id;

        if (!$this->rateLimitService->isAllowed($agencyId, $platform, $action)) {
            $remaining = $this->rateLimitService->getSecondsUntilReset($agencyId, $platform, $action);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Rate limit exceeded for {$platform}. Try again in {$remaining} seconds.",
                    'retry_after' => $remaining,
                ], 429);
            }

            return back()->with('error', "Rate limit exceeded for {$platform}. Please wait before trying again.");
        }

        // Record the action
        $this->rateLimitService->recordAction($agencyId, $platform, $action);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $this->rateLimitService->getLimit($agencyId, $platform, $action));
        $response->headers->set('X-RateLimit-Remaining', $this->rateLimitService->getRemaining($agencyId, $platform, $action));

        return $response;
    }
}
