<?php

namespace App\Http\Middleware;

use App\Models\Agency;
use App\Services\QuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAiCredits
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->agency) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        /** @var Agency $agency */
        $agency = $user->agency;

        // Check if agency has a plan that includes AI
        $plan = config("platform.plans.{$agency->subscription_plan}");
        $hasAiInPlan = ($plan['ai_generations_per_month'] ?? 0) !== 0;

        // If plan includes AI (not zero), allow without credits
        if ($hasAiInPlan) {
            return $next($request);
        }

        // Otherwise, check for purchased credits
        if (! $this->quotaService->hasAiCredits($agency)) {
            return response()->json([
                'error' => 'No AI credits remaining.',
                'upgrade_url' => route('agency.billing'),
                'credits' => 0,
            ], 402);
        }

        // Deduct credit for usage-based plans
        $this->quotaService->deductAiCredit($agency);

        return $next($request);
    }
}
