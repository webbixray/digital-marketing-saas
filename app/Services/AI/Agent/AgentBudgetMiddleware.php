<?php

namespace App\Services\AI\Agent;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentBudgetMiddleware
{
    public function __construct(
        private readonly AgentCostTracker $costTracker,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $agencyId = $request->user()?->agency_id;

        if ($agencyId && $this->costTracker->checkBudgetLimit($agencyId)) {
            $limit = $this->costTracker->getBudgetLimit($agencyId);
            $currentCost = $this->costTracker->getMonthlyCost($agencyId);

            return response()->json([
                'error' => 'Budget limit exceeded',
                'message' => sprintf(
                    'Your agency has reached the monthly AI cost limit of $%.2f. Current spend: $%.2f. Please upgrade your plan or contact support.',
                    $limit,
                    $currentCost,
                ),
                'budget_limit' => $limit,
                'current_spend' => $currentCost,
                'upgrade_url' => route('billing.upgrade'),
            ], 429);
        }

        return $next($request);
    }
}
