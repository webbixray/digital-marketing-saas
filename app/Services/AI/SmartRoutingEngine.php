<?php

namespace App\Services\AI;

use App\Models\Agency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Smart AI Routing Engine
 *
 * Makes real-time decisions about which AI provider to use based on:
 * 1. Cost efficiency (cheapest provider that can handle the task)
 * 2. Latency requirements (fast providers for time-sensitive tasks)
 * 3. Provider health (success rate from recent history)
 * 4. Task type matching (some providers excel at certain tasks)
 * 5. Budget pressure (switch to cheaper providers when near limit)
 */
class SmartRoutingEngine
{
    /**
     * Provider affinity by task type (which provider does best at each task).
     * Scored 0-100.
     */
    protected array $taskAffinity = [
        'reasoning' => [
            'anthropic' => 95, 'openai' => 90, 'nous_portal' => 85,
            'google' => 80, 'mistral' => 75, 'groq' => 60,
            'nvidia_nim' => 55, 'ollama' => 40,
        ],
        'creative' => [
            'openai' => 95, 'anthropic' => 90, 'nous_portal' => 85,
            'google' => 80, 'mistral' => 70, 'groq' => 55,
            'nvidia_nim' => 50, 'ollama' => 35,
        ],
        'fast' => [
            'groq' => 95, 'openai' => 85, 'nvidia_nim' => 80,
            'nous_portal' => 75, 'anthropic' => 70, 'google' => 65,
            'mistral' => 60, 'ollama' => 30,
        ],
        'analysis' => [
            'openai' => 90, 'anthropic' => 90, 'google' => 85,
            'nous_portal' => 80, 'mistral' => 75, 'groq' => 65,
            'nvidia_nim' => 60, 'ollama' => 45,
        ],
        'code' => [
            'openai' => 90, 'anthropic' => 85, 'nous_portal' => 80,
            'google' => 75, 'mistral' => 70, 'groq' => 65,
            'nvidia_nim' => 60, 'ollama' => 50,
        ],
        'embedding' => [
            'openai' => 95, 'google' => 90, 'mistral' => 70,
            'nvidia_nim' => 60, 'groq' => 50, 'anthropic' => 40,
            'nous_portal' => 35, 'ollama' => 20,
        ],
    ];

    /**
     * Health threshold: providers with success rate below this are deprioritized.
     */
    protected const HEALTH_THRESHOLD = 0.7;

    /**
     * Get the best provider for a request.
     *
     * Algorithm:
     * 1. Filter out unhealthy providers (< 70% success rate)
     * 2. Score each provider: (task_affinity * 0.4) + (cost_score * 0.3) + (latency_score * 0.2) + (health_score * 0.1)
     * 3. Sort by score descending
     * 4. Return ordered list with scores
     */
    public function getBestProvider(
        string $taskType,
        int $agencyId,
        array $availableProviders = [],
        bool $budgetPressure = false
    ): array {
        // Get provider health metrics
        $health = $this->getProviderHealth($agencyId);

        // Get cost data
        $costs = $this->getProviderCosts();

        // Get latency data
        $latencies = $this->getProviderLatencies();

        // Get task affinity
        $affinity = $this->taskAffinity[$taskType] ?? $this->taskAffinity['fast'];

        // Build scores
        $scores = [];
        $candidates = empty($availableProviders) ? array_keys($this->taskAffinity['fast']) : $availableProviders;

        foreach ($candidates as $provider) {
            // Skip unhealthy providers (0% success or < 70% success)
            $providerHealth = $health[$provider] ?? 0.95;
            if ($providerHealth < self::HEALTH_THRESHOLD) {
                Log::debug("Provider {$provider} deprioritized due to low health: {$providerHealth}");
                continue;
            }

            // Task affinity score (0-100)
            $affinityScore = $affinity[$provider] ?? 50;

            // Cost score (0-100, higher = cheaper)
            $avgCost = ($costs[$provider]['input'] + $costs[$provider]['output']) / 2;
            $costScore = $avgCost <= 0 ? 100 : max(0, 100 - ($avgCost * 10));

            // Latency score (0-100, higher = faster)
            $latency = $latencies[$provider] ?? 1000;
            $latencyScore = max(0, 100 - ($latency / 20));

            // Health score (0-100)
            $healthScore = $providerHealth * 100;

            // Weighted total
            if ($budgetPressure) {
                // When budget is tight, cost matters more
                $total = ($affinityScore * 0.2) + ($costScore * 0.5) + ($latencyScore * 0.15) + ($healthScore * 0.15);
            } else {
                $total = ($affinityScore * 0.4) + ($costScore * 0.3) + ($latencyScore * 0.2) + ($healthScore * 0.1);
            }

            $scores[$provider] = [
                'provider' => $provider,
                'score' => round($total, 2),
                'affinity' => $affinityScore,
                'cost_score' => round($costScore, 2),
                'latency_score' => round($latencyScore, 2),
                'health_score' => round($healthScore, 2),
                'health' => $providerHealth,
                'avg_cost_per_1m' => $avgCost,
                'avg_latency_ms' => $latency,
            ];
        }

        // Sort by score descending
        uasort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scores;
    }

    /**
     * Record a provider execution result for future routing decisions.
     */
    public function recordResult(int $agencyId, string $provider, string $taskType, bool $success, float $cost, int $latencyMs): void
    {
        $key = "ai_routing:{$agencyId}:{$provider}";
        $history = Cache::get($key, ['successes' => 0, 'failures' => 0, 'total_cost' => 0, 'total_latency' => 0]);

        if ($success) {
            $history['successes']++;
        } else {
            $history['failures']++;
        }
        $history['total_cost'] += $cost;
        $history['total_latency'] += $latencyMs;

        // Keep last 100 results
        $total = $history['successes'] + $history['failures'];
        if ($total > 100) {
            $history['successes'] = (int) ($history['successes'] * 0.9);
            $history['failures'] = (int) ($history['failures'] * 0.9);
        }

        Cache::put($key, $history, now()->addDays(7));
    }

    /**
     * Get routing dashboard data for the agency.
     */
    public function getDashboardData(int $agencyId): array
    {
        $providers = ['openai', 'anthropic', 'google', 'mistral', 'groq', 'nvidia_nim', 'nous_portal', 'ollama'];
        $data = [];

        foreach ($providers as $provider) {
            $health = $this->getProviderHealth($agencyId);
            $costs = $this->getProviderCosts();
            $latencies = $this->getProviderLatencies();

            $data[$provider] = [
                'health' => $health[$provider] ?? null,
                'cost_per_1m_input' => $costs[$provider]['input'] ?? 0,
                'cost_per_1m_output' => $costs[$provider]['output'] ?? 0,
                'avg_latency_ms' => $latencies[$provider] ?? null,
                'best_for' => $this->getBestTasksForProvider($provider),
            ];
        }

        return $data;
    }

    /**
     * Get which tasks a provider excels at.
     */
    protected function getBestTasksForProvider(string $provider): array
    {
        $tasks = [];
        foreach ($this->taskAffinity as $task => $scores) {
            if (($scores[$provider] ?? 0) >= 80) {
                $tasks[] = $task;
            }
        }
        return $tasks;
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    protected function getProviderHealth(int $agencyId): array
    {
        $providers = ['openai', 'anthropic', 'google', 'mistral', 'groq', 'nvidia_nim', 'nous_portal', 'ollama'];
        $health = [];

        foreach ($providers as $provider) {
            $key = "ai_routing:{$agencyId}:{$provider}";
            $history = Cache::get($key);

            if (! $history || ($history['successes'] + $history['failures']) === 0) {
                $health[$provider] = 0.95; // Default: assume healthy
            } else {
                $health[$provider] = $history['successes'] / ($history['successes'] + $history['failures']);
            }
        }

        return $health;
    }

    protected function getProviderCosts(): array
    {
        return [
            'openai' => ['input' => 2.50, 'output' => 10.00],
            'anthropic' => ['input' => 3.00, 'output' => 15.00],
            'google' => ['input' => 1.25, 'output' => 5.00],
            'mistral' => ['input' => 2.00, 'output' => 6.00],
            'groq' => ['input' => 0.10, 'output' => 0.10],
            'nvidia_nim' => ['input' => 0.40, 'output' => 1.00],
            'nous_portal' => ['input' => 0.20, 'output' => 0.60],
            'ollama' => ['input' => 0.00, 'output' => 0.00],
        ];
    }

    protected function getProviderLatencies(): array
    {
        return [
            'openai' => 1200,
            'anthropic' => 1500,
            'google' => 1100,
            'mistral' => 1300,
            'groq' => 400,
            'nvidia_nim' => 700,
            'nous_portal' => 800,
            'ollama' => 200,
        ];
    }
}
