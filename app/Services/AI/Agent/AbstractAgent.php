<?php

namespace App\Services\AI\Agent;

use App\Models\Agency;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class AbstractAgent implements AgentInterface
{
    protected AgentMemory $memory;

    protected AiGateway $gateway;

    protected string $name;

    /**
     * Task types supported by this agent.
     *
     * @var array<string>
     */
    protected array $supportedTaskTypes = [];

    /**
     * In-memory execution stats (persisted to storage).
     */
    protected array $executionStats = [];

    public function __construct(AgentMemory $memory, AiGateway $gateway)
    {
        $this->memory = $memory;
        $this->gateway = $gateway;
        $this->loadMemory();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTaskTypes(): array
    {
        return $this->supportedTaskTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(string $taskType): bool
    {
        return in_array($taskType, $this->supportedTaskTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalExecuted();

        if ($total === 0) {
            return 0.5;
        }

        $successes = $this->executionStats['successes'] ?? 0;

        return $successes / $total;
    }

    /**
     * {@inheritdoc}
     */
    public function getSpeedScore(): float
    {
        $times = $this->executionStats['execution_times'] ?? [];

        if (empty($times)) {
            return 0.5;
        }

        $avg = array_sum($times) / count($times);

        // Normalize: < 1s = 1.0, > 30s = 0.0
        if ($avg <= 1000) {
            return 1.0;
        }
        if ($avg >= 30000) {
            return 0.0;
        }

        return 1.0 - (($avg - 1000) / 29000);
    }

    /**
     * {@inheritdoc}
     */
    public function getCostScore(): float
    {
        $costs = $this->executionStats['costs'] ?? [];

        if (empty($costs)) {
            return 0.5;
        }

        $avg = array_sum($costs) / count($costs);

        // Normalize: $0 = 1.0, $0.10+ = 0.0
        if ($avg <= 0) {
            return 1.0;
        }
        if ($avg >= 0.10) {
            return 0.0;
        }

        return 1.0 - ($avg / 0.10);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalExecuted(): int
    {
        return $this->executionStats['total'] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCost(): float
    {
        return $this->executionStats['total_cost'] ?? 0.0;
    }

    /**
     * Send a request through the AI gateway.
     */
    protected function callAi(AiRequest $request, Agency $agency): AiResponse
    {
        return $this->gateway->send($request, $agency);
    }

    /**
     * Record an execution result to memory and persist it.
     */
    protected function recordExecution(string $taskType, AgentResult $result): void
    {
        // Record to shared AgentMemory
        $this->memory->recordResult($this->name, $taskType, $result);

        // Update local stats
        $this->executionStats['total'] = ($this->executionStats['total'] ?? 0) + 1;
        $this->executionStats['total_cost'] = ($this->executionStats['total_cost'] ?? 0) + $result->costUsd;

        if ($result->success) {
            $this->executionStats['successes'] = ($this->executionStats['successes'] ?? 0) + 1;
        }

        $this->executionStats['execution_times'][] = $result->executionTimeMs;
        $this->executionStats['costs'][] = $result->costUsd;

        if (isset($result->metadata['engagement_score'])) {
            $this->executionStats['engagement_scores'][] = $result->metadata['engagement_score'];
        }

        if (isset($result->metadata['prediction_accuracy'])) {
            $this->executionStats['prediction_accuracies'][] = $result->metadata['prediction_accuracy'];
        }

        if (isset($result->metadata['vulnerability_severity'])) {
            $this->executionStats['vulnerability_findings'][] = $result->metadata;
        }

        // Trim arrays to prevent unbounded growth
        $maxEntries = 100;
        foreach (['execution_times', 'costs', 'engagement_scores', 'prediction_accuracies'] as $key) {
            if (isset($this->executionStats[$key]) && count($this->executionStats[$key]) > $maxEntries) {
                $this->executionStats[$key] = array_slice($this->executionStats[$key], -$maxEntries);
            }
        }

        $this->persistMemory();
    }

    /**
     * Get the storage path for this agent's memory.
     */
    protected function getMemoryPath(AgentContext $context): string
    {
        $agencyId = $context->agencyId ?: 'global';

        return "agent_memory/{$agencyId}/{$this->name}.json";
    }

    /**
     * Persist current execution stats to storage.
     */
    protected function persistMemory(): void
    {
        try {
            $data = [
                'agent_name' => $this->name,
                'supported_task_types' => $this->supportedTaskTypes,
                'stats' => $this->executionStats,
                'updated_at' => now()->toIso8601String(),
            ];

            Storage::put(
                "agent_memory/global/{$this->name}.json",
                json_encode($data, JSON_PRETTY_PRINT)
            );
        } catch (\Exception $e) {
            Log::warning("Failed to persist agent memory for [{$this->name}]: {$e->getMessage()}");
        }
    }

    /**
     * Load persisted memory from storage.
     */
    protected function loadMemory(): void
    {
        try {
            $path = "agent_memory/global/{$this->name}.json";

            if (Storage::exists($path)) {
                $data = json_decode(Storage::get($path), true);
                $this->executionStats = $data['stats'] ?? [];
            }
        } catch (\Exception $e) {
            Log::debug("No existing memory for agent [{$this->name}], starting fresh.");
        }
    }

    /**
     * Persist task-specific results for an agency.
     */
    protected function persistAgencyResults(AgentContext $context, string $taskType, AgentResult $result): void
    {
        try {
            $path = $this->getMemoryPath($context);
            $existing = Storage::exists($path) ? json_decode(Storage::get($path), true) : [];

            $existing['results'][] = [
                'task_type' => $taskType,
                'success' => $result->success,
                'output_preview' => substr($result->output, 0, 500),
                'cost_usd' => $result->costUsd,
                'execution_time_ms' => $result->executionTimeMs,
                'metadata' => $result->metadata,
                'timestamp' => now()->toIso8601String(),
            ];

            // Keep only last 200 results per agency
            if (count($existing['results'] ?? []) > 200) {
                $existing['results'] = array_slice($existing['results'], -200);
            }

            $existing['stats'] = [
                'total' => count($existing['results']),
                'successes' => count(array_filter($existing['results'], fn ($r) => $r['success'])),
                'last_updated' => now()->toIso8601String(),
            ];

            Storage::put($path, json_encode($existing, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::warning("Failed to persist agency results for [{$this->name}]: {$e->getMessage()}");
        }
    }

    /**
     * Get learned patterns from agency-specific memory.
     */
    protected function getLearnedPatterns(AgentContext $context, ?string $taskType = null): array
    {
        try {
            $path = $this->getMemoryPath($context);

            if (! Storage::exists($path)) {
                return [];
            }

            $data = json_decode(Storage::get($path), true);

            if ($taskType) {
                return array_filter(
                    $data['results'] ?? [],
                    fn ($r) => $r['task_type'] === $taskType
                );
            }

            return $data['results'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
