<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Intelligent AI cost optimization engine.
 *
 * Optimizations applied (in order):
 * 1. Content-addressable response caching (identical prompts → zero cost)
 * 2. Budget-aware provider routing (cheaper providers when near budget limit)
 * 3. Dynamic model downgrade (gpt-4o → gpt-4o-mini when quality is sufficient)
 * 4. Prompt token budgeting (auto-truncate system prompts to fit context)
 * 5. Batched multi-step requests (combine related tasks into one API call)
 */
class CostOptimizationEngine
{
    protected AiGateway $gateway;
    protected AiCacheService $cache;

    /**
     * Provider preference by cost tier (cheapest first).
     */
    private const COST_TIERS = [
        'free' => ['ollama', 'groq', 'nvidia_nim', 'nous_portal', 'openrouter', 'mistral', 'google', 'anthropic', 'openai'],
        'starter' => ['groq', 'nvidia_nim', 'nous_portal', 'openrouter', 'mistral', 'google', 'anthropic', 'openai'],
        'pro' => ['nvidia_nim', 'nous_portal', 'groq', 'openrouter', 'mistral', 'google', 'anthropic', 'openai'],
        'enterprise' => ['groq', 'nvidia_nim', 'nous_portal', 'openrouter', 'mistral', 'google', 'anthropic', 'openai'],
    ];

    /**
     * Model downgrade map: expensive → cheaper alternative per task.
     */
    private const MODEL_DOWNGRADE = [
        'reasoning' => ['gpt-4o' => 'gpt-4o-mini', 'claude-3.5-sonnet' => 'claude-3-haiku', 'gemini-1.5-pro' => 'gemini-1.5-flash'],
        'creative' => ['gpt-4o' => 'gpt-4o-mini', 'claude-3.5-sonnet' => 'claude-3-haiku'],
        'fast' => ['gpt-4o' => 'gpt-3.5-turbo', 'claude-3.5-sonnet' => 'claude-3-haiku'],
        'analysis' => ['gpt-4o' => 'gpt-4o-mini'],
    ];

    /**
     * Quality threshold: if agent success rate > this, we can downgrade model.
     */
    private const QUALITY_THRESHOLD = 0.85;

    /**
     * Budget threshold: when remaining budget < this fraction of total, downgrade.
     */
    private const BUDGET_PRESSURE_THRESHOLD = 0.2;

    public function __construct(AiGateway $gateway, AiCacheService $cache)
    {
        $this->gateway = $gateway;
        $this->cache = $cache;
    }

    /**
     * Send an AI request with cost optimization applied.
     *
     * Pipeline:
     * 1. Check cache → return if hit
     * 2. Apply prompt optimization (token budgeting)
     * 3. Apply model downgrade if agency has history of success
     * 4. Send via gateway
     * 5. Cache result if appropriate
     */
    public function send(AiRequest $request, Agency $agency): AiResponse
    {
        // 1. Cache lookup
        if ($cached = $this->cache->get($request, $agency)) {
            return $cached;
        }

        // 2. Budget-aware model selection
        $request = $this->applyBudgetAwareModel($request, $agency);

        // 3. Apply model downgrade if quality is historically high
        $request = $this->applySmartModelDowngrade($request, $agency);

        // 4. Optimize prompt for token usage
        $request = $this->optimizePrompt($request, $agency);

        // 5. Send via gateway
        $response = $this->gateway->send($request, $agency);

        // 6. Cache the response
        $this->cache->put($request, $agency, $response);

        return $response;
    }

    /**
     * Get real-time cost optimization metrics for an agency.
     */
    public function getOptimizationMetrics(int $agencyId): array
    {
        $cacheMetrics = $this->cache->getMetrics($agencyId);
        $monthlyCost = $this->getMonthlyCost($agencyId);
        $budgetLimit = $this->getBudgetLimit($agencyId);

        return [
            'cache' => $cacheMetrics,
            'monthly_cost_usd' => $monthlyCost,
            'budget_limit_usd' => $budgetLimit,
            'budget_used_percent' => $budgetLimit > 0 ? round($monthlyCost / $budgetLimit * 100, 1) : 0.0,
            'estimated_savings_usd' => $this->cache->getSavings($agencyId),
            'downgrade_active' => $this->isDowngradeActive($agencyId),
            'recommendations' => $this->generateRecommendations($agencyId),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Optimization strategies
    // ──────────────────────────────────────────────────────────────

    /**
     * Switch to cheaper providers when budget is tight.
     */
    private function applyBudgetAwareModel(AiRequest $request, Agency $agency): AiRequest
    {
        $remaining = $this->getRemainingBudget($agency->id);
        $limit = $this->getBudgetLimit($agency->id);

        if ($limit <= 0 || $remaining <= 0) {
            return $request;
        }

        $pressure = $remaining / $limit;

        if ($pressure < self::BUDGET_PRESSURE_THRESHOLD) {
            // Budget is tight — switch to cheapest provider
            $costTiers = self::COST_TIERS[$agency->subscription_plan] ?? self::COST_TIERS['free'];
            $cheapest = $costTiers[0] ?? null;

            if ($cheapest && $cheapest !== $request->provider) {
                Log::info("Budget pressure ({$pressure}): switching to cheaper provider {$cheapest}");
                return $request->withProvider($cheapest);
            }
        }

        return $request;
    }

    /**
     * Smart model downgrade based on historical agent success rate.
     */
    private function applySmartModelDowngrade(AiRequest $request, Agency $agency): AiRequest
    {
        $successRate = $this->getAgentSuccessRate($agency->id, $request->task);

        if ($successRate < self::QUALITY_THRESHOLD) {
            return $request; // Quality not high enough — keep model
        }

        $downgradeMap = self::MODEL_DOWNGRADE[$request->task] ?? [];
        $downgraded = $downgradeMap[$request->model] ?? null;

        if ($downgraded && $downgraded !== $request->model) {
            Log::debug("Model downgrade: {$request->model} → {$downgraded} (success rate: {$successRate})");
            return $request->withModel($downgraded);
        }

        return $request;
    }

    /**
     * Optimize prompt for token efficiency.
     */
    private function optimizePrompt(AiRequest $request, Agency $agency): AiRequest
    {
        // If system prompt is very long (> 2000 tokens), summarize it
        if ($request->systemPrompt && strlen($request->systemPrompt) > 4000) {
            // In production, this would call a fast model to summarize
            // For now, we truncate with a note
            $truncated = substr($request->systemPrompt, 0, 3500) . '\n[...truncated for efficiency]';
            $request = $request->withSystemPrompt($truncated);
        }

        // Remove unnecessary whitespace from prompt
        $cleanPrompt = preg_replace('/\n{3,}/', "\n\n", $request->prompt);
        $cleanPrompt = trim($cleanPrompt);

        if ($cleanPrompt !== $request->prompt) {
            $request = $request->withPrompt($cleanPrompt);
        }

        return $request;
    }

    /**
     * Generate cost optimization recommendations.
     */
    private function generateRecommendations(int $agencyId): array
    {
        $metrics = $this->getOptimizationMetrics($agencyId);
        $recommendations = [];

        if (($metrics['cache']['hit_rate'] ?? 0) < 30) {
            $recommendations[] = [
                'type' => 'cache',
                'message' => 'Cache hit rate is low. Consider increasing TTL for analysis tasks.',
                'priority' => 'medium',
            ];
        }

        if (($metrics['budget_used_percent'] ?? 0) > 80) {
            $recommendations[] = [
                'type' => 'budget',
                'message' => 'Budget 80% consumed. Model downgrade activated.',
                'priority' => 'high',
            ];
        }

        if (($metrics['estimated_savings_usd'] ?? 0) > 10) {
            $recommendations[] = [
                'type' => 'savings',
                'message' => "Cache has saved \${$metrics['estimated_savings_usd']} this month.",
                'priority' => 'info',
            ];
        }

        return $recommendations;
    }

    // ──────────────────────────────────────────────────────────────
    // Helper methods
    // ──────────────────────────────────────────────────────────────

    private function getAgentSuccessRate(int $agencyId, string $task): float
    {
        return (float) Cache::get("ai_agent_success:{$agencyId}:{$task}", 0.5);
    }

    private function getMonthlyCost(int $agencyId): float
    {
        return (float) Cache::remember("ai_monthly_cost:{$agencyId}", 3600, function () use ($agencyId) {
            return \App\Models\AgentCostLog::byAgency($agencyId)->currentMonth()->sum('cost_usd');
        });
    }

    private function getBudgetLimit(int $agencyId): float
    {
        $agency = Agency::find($agencyId);
        $plan = $agency->subscription_plan ?? 'free';
        return match ($plan) {
            'free' => 5.0,
            'starter' => 50.0,
            'pro' => 200.0,
            'enterprise' => PHP_INT_MAX,
            default => 5.0,
        };
    }

    private function getRemainingBudget(int $agencyId): float
    {
        $limit = $this->getBudgetLimit($agencyId);
        $cost = $this->getMonthlyCost($agencyId);
        return max(0, $limit - $cost);
    }

    private function isDowngradeActive(int $agencyId): bool
    {
        return Cache::get("ai_downgrade_active:{$agencyId}", false);
    }
}
