<?php

namespace App\Services\AI\Agent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AgentMemory
{
    private const CACHE_KEY = 'agent_orchestrator_memory';

    private const LEARNED_PATTERNS_KEY = 'agent_learned_patterns';

    private const MAX_HISTORY = 1000;

    /**
     * Record a result for learning purposes.
     */
    public function recordResult(string $agentName, string $taskType, AgentResult $result): void
    {
        $history = $this->getHistory();

        $entry = [
            'agent_name' => $agentName,
            'task_type' => $taskType,
            'success' => $result->success,
            'cost_usd' => $result->costUsd,
            'execution_time_ms' => $result->executionTimeMs,
            'timestamp' => time(),
        ];

        $history[] = $entry;

        // Trim to max history size
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }

        $this->storeHistory($history);

        Log::debug("AgentMemory: recorded result for agent [{$agentName}], task type [{$taskType}]");
    }

    /**
     * Get the success rate for a specific agent on a specific task type.
     */
    public function getSuccessRate(string $agentName, string $taskType): float
    {
        $history = $this->getHistory();

        $relevant = array_filter($history, fn ($entry) => $entry['agent_name'] === $agentName && $entry['task_type'] === $taskType
        );

        if (empty($relevant)) {
            return 0.5; // Default neutral rate for unknown combinations
        }

        $successes = array_filter($relevant, fn ($entry) => $entry['success']);

        return count($successes) / count($relevant);
    }

    /**
     * Get the average cost for an agent on a task type.
     */
    public function getAverageCost(string $agentName, string $taskType): float
    {
        $history = $this->getHistory();

        $relevant = array_filter($history, fn ($entry) => $entry['agent_name'] === $agentName && $entry['task_type'] === $taskType
        );

        if (empty($relevant)) {
            return 0.0;
        }

        $costs = array_column($relevant, 'cost_usd');

        return array_sum($costs) / count($costs);
    }

    /**
     * Get the average execution time for an agent on a task type.
     */
    public function getAverageExecutionTime(string $agentName, string $taskType): float
    {
        $history = $this->getHistory();

        $relevant = array_filter($history, fn ($entry) => $entry['agent_name'] === $agentName && $entry['task_type'] === $taskType
        );

        if (empty($relevant)) {
            return 0.0;
        }

        $times = array_column($relevant, 'execution_time_ms');

        return array_sum($times) / count($times);
    }

    /**
     * Get per-agent statistics aggregated across all task types.
     *
     * @return array<string, array{total: int, successes: int, success_rate: float, total_cost: float}>
     */
    public function getAgentStats(): array
    {
        $history = $this->getHistory();
        $stats = [];

        foreach ($history as $entry) {
            $name = $entry['agent_name'];

            if (! isset($stats[$name])) {
                $stats[$name] = [
                    'total' => 0,
                    'successes' => 0,
                    'total_cost' => 0.0,
                ];
            }

            $stats[$name]['total']++;

            if ($entry['success']) {
                $stats[$name]['successes']++;
            }

            $stats[$name]['total_cost'] += $entry['cost_usd'];
        }

        // Calculate success rates
        foreach ($stats as $name => &$stat) {
            $stat['success_rate'] = $stat['total'] > 0
                ? $stat['successes'] / $stat['total']
                : 0.0;
        }

        return $stats;
    }

    /**
     * Record a learned pattern from an agent's feedback loop.
     *
     * @param  string  $agentName  The agent that discovered this pattern
     * @param  string  $taskType  The task type this pattern applies to
     * @param  array  $pattern  The pattern data (e.g., best parameters, correlations)
     * @param  float  $confidence  Confidence score 0.0 to 1.0
     */
    public function recordLearnedPattern(
        string $agentName,
        string $taskType,
        array $pattern,
        float $confidence
    ): void {
        $patterns = $this->getLearnedPatterns();

        $key = md5(json_encode($pattern));

        // Update existing pattern or add new
        if (isset($patterns[$key])) {
            $patterns[$key]['occurrence_count']++;
            $patterns[$key]['confidence'] = max($patterns[$key]['confidence'], $confidence);
            $patterns[$key]['last_seen'] = time();
        } else {
            $patterns[$key] = [
                'agent_name' => $agentName,
                'task_type' => $taskType,
                'pattern' => $pattern,
                'confidence' => $confidence,
                'first_seen' => time(),
                'last_seen' => time(),
                'occurrence_count' => 1,
            ];
        }

        $this->storeLearnedPatterns($patterns);

        Log::debug("AgentMemory: recorded learned pattern for [{$agentName}], task [{$taskType}]");
    }

    /**
     * Get all learned patterns, optionally filtered by agent or task type.
     *
     * @param  string|null  $agentName  Filter by agent name
     * @param  string|null  $taskType  Filter by task type
     * @param  float  $minConfidence  Minimum confidence threshold
     * @return array<int, array>
     */
    public function getLearnedPatterns(
        ?string $agentName = null,
        ?string $taskType = null,
        float $minConfidence = 0.0
    ): array {
        $patterns = Cache::get(self::LEARNED_PATTERNS_KEY, []);

        if ($agentName !== null) {
            $patterns = array_filter($patterns, fn ($p) => ($p['agent_name'] ?? null) === $agentName);
        }

        if ($taskType !== null) {
            $patterns = array_filter($patterns, fn ($p) => ($p['task_type'] ?? null) === $taskType);
        }

        if ($minConfidence > 0) {
            $patterns = array_filter($patterns, fn ($p) => ($p['confidence'] ?? 0) >= $minConfidence);
        }

        // Sort by confidence descending, then occurrence count descending
        uasort($patterns, function ($a, $b) {
            $confDiff = ($b['confidence'] ?? 0) <=> ($a['confidence'] ?? 0);
            if ($confDiff !== 0) {
                return $confDiff;
            }

            return ($b['occurrence_count'] ?? 0) <=> ($a['occurrence_count'] ?? 0);
        });

        return array_values($patterns);
    }

    /**
     * Get the most reliable learned patterns across all agents.
     *
     * @param  int  $limit  Maximum number of patterns to return
     * @return array<int, array>
     */
    public function getTopLearnedPatterns(int $limit = 10): array
    {
        $patterns = $this->getLearnedPatterns(minConfidence: 0.7);

        return array_slice($patterns, 0, $limit);
    }

    /**
     * Store learned patterns in cache.
     *
     * @param  array<string, array>  $patterns
     */
    private function storeLearnedPatterns(array $patterns): void
    {
        // Limit total patterns stored
        if (count($patterns) > 500) {
            // Keep highest confidence/most occurring patterns
            uasort($patterns, function ($a, $b) {
                $scoreA = ($a['confidence'] ?? 0) * ($a['occurrence_count'] ?? 1);
                $scoreB = ($b['confidence'] ?? 0) * ($b['occurrence_count'] ?? 1);

                return $scoreB <=> $scoreA;
            });
            $patterns = array_slice($patterns, 0, 500, true);
        }

        Cache::put(self::LEARNED_PATTERNS_KEY, $patterns, now()->addDays(14));
    }

    /**
     * Clear learned patterns.
     */
    public function clearLearnedPatterns(): void
    {
        Cache::forget(self::LEARNED_PATTERNS_KEY);
    }

    /**
     * Get all historical records.
     *
     * @return array<int, array>
     */
    public function getHistory(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }

    /**
     * Store history in cache.
     *
     * @param  array<int, array>  $history
     */
    private function storeHistory(array $history): void
    {
        Cache::put(self::CACHE_KEY, $history, now()->addDays(7));
    }

    /**
     * Clear all memory including learned patterns.
     */
    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LEARNED_PATTERNS_KEY);
    }
}
