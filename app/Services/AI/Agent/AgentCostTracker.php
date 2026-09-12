<?php

namespace App\Services\AI\Agent;

use App\Models\Agency;
use App\Models\AgentCostLog;
use Illuminate\Support\Facades\Log;

class AgentCostTracker
{
    /**
     * Budget limits per plan (USD/month).
     */
    private const PLAN_LIMITS = [
        'free' => 5.0,
        'starter' => 50.0,
        'pro' => 200.0,
        'enterprise' => -1.0, // unlimited
    ];

    /**
     * Record a cost entry for an agent execution.
     */
    public function recordCost(int $agencyId, string $agentName, float $costUsd, string $taskType, int $tokensUsed = 0): void
    {
        try {
            AgentCostLog::create([
                'agency_id' => $agencyId,
                'agent_name' => $agentName,
                'task_type' => $taskType,
                'cost_usd' => $costUsd,
                'tokens_used' => $tokensUsed,
                'executed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("AgentCostTracker: failed to record cost for agency [{$agencyId}], agent [{$agentName}]: {$e->getMessage()}");
        }
    }

    /**
     * Get total AI cost for current month.
     */
    public function getMonthlyCost(int $agencyId): float
    {
        return (float) AgentCostLog::byAgency($agencyId)
            ->currentMonth()
            ->sum('cost_usd');
    }

    /**
     * Get cost breakdown by agent for current month.
     */
    public function getCostByAgent(int $agencyId): array
    {
        $logs = AgentCostLog::byAgency($agencyId)
            ->currentMonth()
            ->selectRaw('agent_name, SUM(cost_usd) as total_cost, SUM(tokens_used) as total_tokens, COUNT(*) as task_count')
            ->groupBy('agent_name')
            ->get();

        $result = [];
        foreach ($logs as $log) {
            $result[$log->agent_name] = [
                'total_cost_usd' => (float) $log->total_cost,
                'total_tokens' => (int) $log->total_tokens,
                'task_count' => (int) $log->task_count,
                'avg_cost_per_task' => (int) $log->task_count > 0
                    ? round((float) $log->total_cost / (int) $log->task_count, 6)
                    : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Get daily cost trend for the specified number of days.
     */
    public function getCostTrend(int $agencyId, int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $logs = AgentCostLog::byAgency($agencyId)
            ->inDateRange($start, $end)
            ->selectRaw('DATE(executed_at) as date, SUM(cost_usd) as daily_cost, COUNT(*) as task_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days with zero cost
        $trend = [];
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);

        $costMap = [];
        foreach ($logs as $log) {
            $costMap[$log->date] = [
                'cost_usd' => (float) $log->daily_cost,
                'task_count' => (int) $log->task_count,
            ];
        }

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $trend[$dateStr] = $costMap[$dateStr] ?? [
                'cost_usd' => 0.0,
                'task_count' => 0,
            ];
        }

        return $trend;
    }

    /**
     * Check if agency has exceeded its budget limit.
     */
    public function checkBudgetLimit(int $agencyId): bool
    {
        $limit = $this->getBudgetLimit($agencyId);

        // Unlimited budget (enterprise)
        if ($limit < 0) {
            return false;
        }

        $currentCost = $this->getMonthlyCost($agencyId);

        return $currentCost >= $limit;
    }

    /**
     * Get budget limit based on agency's subscription plan.
     */
    public function getBudgetLimit(int $agencyId): float
    {
        $agency = Agency::find($agencyId);

        if (! $agency) {
            return self::PLAN_LIMITS['free'];
        }

        return self::PLAN_LIMITS[$agency->subscription_plan] ?? self::PLAN_LIMITS['free'];
    }

    /**
     * Get remaining budget for the agency.
     */
    public function getRemainingBudget(int $agencyId): float
    {
        $limit = $this->getBudgetLimit($agencyId);

        // Unlimited budget (enterprise)
        if ($limit < 0) {
            return -1.0;
        }

        $currentCost = $this->getMonthlyCost($agencyId);

        return max(0.0, $limit - $currentCost);
    }
}
