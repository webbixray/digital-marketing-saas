<?php

namespace App\Services\AI\Agent;

use App\Services\AI\Gateway\AiGateway;
use Illuminate\Support\Facades\Log;

class AgentOrchestrator
{
    /**
     * @var array<string, AgentInterface>
     */
    private array $agents = [];

    /**
     * @var array<string, array<string, float>>
     */
    private array $routingWeights = [];

    public function __construct(
        private readonly AiGateway $aiGateway,
        private readonly AgentMemory $memory,
        private readonly ?AgentCostTracker $costTracker = null,
    ) {}

    /**
     * Register an agent with the orchestrator.
     */
    public function registerAgent(string $name, AgentInterface $agent): void
    {
        $this->agents[$name] = $agent;

        // Initialize routing weights for this agent
        if (! isset($this->routingWeights[$name])) {
            $this->routingWeights[$name] = [];
        }

        Log::info("AgentOrchestrator: registered agent [{$name}]");
    }

    /**
     * Dispatch a task to the best agent.
     */
    public function dispatch(AgentTask $task, ?AgentContext $context = null): AgentResult
    {
        $agent = $this->getBestAgentFor($task->type);

        if ($agent === null) {
            Log::warning("AgentOrchestrator: no agent found for task type [{$task->type}]");

            return AgentResult::failure(
                taskId: $task->id,
                agentName: 'unknown',
                error: "No agent available for task type: {$task->type}",
            );
        }

        $agentName = $agent->getName();
        $startTime = microtime(true);

        try {
            $result = $agent->execute($task, $context ?? new AgentContext);
            $elapsedMs = (microtime(true) - $startTime) * 1000;

            $result = new AgentResult(
                taskId: $result->taskId,
                agentName: $result->agentName,
                success: $result->success,
                output: $result->output,
                costUsd: $result->costUsd,
                tokensUsed: $result->tokensUsed,
                executionTimeMs: $elapsedMs,
                error: $result->error,
                metadata: $result->metadata,
                timestamp: time(),
            );

            // Record result in memory for learning
            $this->memory->recordResult($agentName, $task->type, $result);

            // Record cost if tracker is available and context has agencyId
            if ($this->costTracker && $context && $context->agencyId > 0) {
                $this->costTracker->recordCost(
                    agencyId: $context->agencyId,
                    agentName: $agentName,
                    costUsd: $result->costUsd,
                    taskType: $task->type,
                    tokensUsed: $result->tokensUsed,
                );
            }

            Log::info("AgentOrchestrator: task [{$task->id}] dispatched to [{$agentName}], success: ".($result->success ? 'yes' : 'no'));

            return $result;
        } catch (\Exception $e) {
            $elapsedMs = (microtime(true) - $startTime) * 1000;

            Log::error("AgentOrchestrator: agent [{$agentName}] failed task [{$task->id}]: {$e->getMessage()}");

            $result = AgentResult::failure(
                taskId: $task->id,
                agentName: $agentName,
                error: $e->getMessage(),
                metadata: ['exception_class' => get_class($e)],
            );

            $this->memory->recordResult($agentName, $task->type, $result);

            return $result;
        }
    }

    /**
     * Execute a multi-step workflow where each step's output feeds into the next.
     *
     * @param  array<AgentTask>  $tasks
     * @return array<AgentResult>
     */
    public function dispatchWorkflow(array $tasks, ?AgentContext $context = null): array
    {
        $results = [];
        $previousOutput = null;

        foreach ($tasks as $index => $task) {
            // If we have previous output, enrich the task with it
            if ($previousOutput !== null) {
                $task = $task->withPreviousOutput($previousOutput);
            }

            $result = $this->dispatch($task, $context);
            $results[] = $result;

            // If a step fails, stop the workflow
            if (! $result->success) {
                Log::warning("AgentOrchestrator: workflow stopped at step [{$index}], agent [{$result->agentName}] failed");
                break;
            }

            $previousOutput = $result->output;
        }

        return $results;
    }

    /**
     * Get agent statistics: success rates, costs, usage per agent.
     */
    public function getAgentStats(): array
    {
        $memoryStats = $this->memory->getAgentStats();
        $stats = [];

        foreach ($this->agents as $name => $agent) {
            $memoryStat = $memoryStats[$name] ?? [
                'total' => 0,
                'successes' => 0,
                'total_cost' => 0.0,
                'success_rate' => 0.0,
            ];

            $stats[$name] = [
                'name' => $name,
                'success_rate' => isset($memoryStat['total']) && $memoryStat['total'] > 0
                    ? $memoryStat['success_rate']
                    : $agent->getSuccessRate(),
                'total_executed' => $memoryStat['total'] ?? $agent->getTotalExecuted(),
                'total_successes' => $memoryStat['successes'] ?? 0,
                'total_cost' => $memoryStat['total_cost'] ?? $agent->getTotalCost(),
                'avg_cost_per_task' => isset($memoryStat['total']) && $memoryStat['total'] > 0
                    ? $memoryStat['total_cost'] / $memoryStat['total']
                    : 0.0,
                'speed_score' => $agent->getSpeedScore(),
                'cost_score' => $agent->getCostScore(),
                'supported_types' => $agent->getSupportedTaskTypes(),
            ];
        }

        return $stats;
    }

    /**
     * Pick the best agent for a given task type based on scoring.
     *
     * Scoring: agent.getSuccessRate() * 0.6 + speed_score * 0.2 + cost_score * 0.2
     */
    public function getBestAgentFor(string $taskType): ?AgentInterface
    {
        $candidates = [];

        foreach ($this->agents as $name => $agent) {
            if ($agent->canHandle($taskType)) {
                $candidates[$name] = $agent;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        $bestAgent = null;
        $bestScore = -1;

        foreach ($candidates as $name => $agent) {
            // Use memory-enhanced success rate if available
            $memoryRate = $this->memory->getSuccessRate($name, $taskType);
            $successRate = $memoryRate > 0 ? $memoryRate : $agent->getSuccessRate();

            $score = $this->calculateScore($successRate, $agent->getSpeedScore(), $agent->getCostScore());

            // Apply any learned routing weights
            if (isset($this->routingWeights[$name][$taskType])) {
                $score *= $this->routingWeights[$name][$taskType];
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAgent = $agent;
            }
        }

        return $bestAgent;
    }

    /**
     * Analyze all results and update routing weights for better future decisions.
     */
    public function learnFromResults(): void
    {
        $history = $this->memory->getHistory();

        if (empty($history)) {
            return;
        }

        // Calculate task-type-specific performance
        $taskTypeStats = [];
        foreach ($history as $entry) {
            $taskType = $entry['task_type'];
            $agentName = $entry['agent_name'];

            if (! isset($taskTypeStats[$taskType])) {
                $taskTypeStats[$taskType] = [];
            }

            if (! isset($taskTypeStats[$taskType][$agentName])) {
                $taskTypeStats[$taskType][$agentName] = [
                    'total' => 0,
                    'successes' => 0,
                    'avg_cost' => 0,
                ];
            }

            $taskTypeStats[$taskType][$agentName]['total']++;

            if ($entry['success']) {
                $taskTypeStats[$taskType][$agentName]['successes']++;
            }
        }

        // Update routing weights based on task-type-specific performance
        foreach ($taskTypeStats as $taskType => $agentStats) {
            foreach ($agentStats as $name => $stat) {
                $successRate = $stat['total'] > 0 ? $stat['successes'] / $stat['total'] : 0;

                // Adjust weight: boost high-success agents, penalize low-success ones
                if ($successRate >= 0.9) {
                    $this->routingWeights[$name][$taskType] = 1.2;
                } elseif ($successRate >= 0.7) {
                    $this->routingWeights[$name][$taskType] = 1.0;
                } elseif ($successRate >= 0.5) {
                    $this->routingWeights[$name][$taskType] = 0.8;
                } else {
                    $this->routingWeights[$name][$taskType] = 0.5;
                }
            }
        }

        Log::info('AgentOrchestrator: learning complete, weights updated for '.count($taskTypeStats).' task types');
    }

    /**
     * Get all registered agent names.
     *
     * @return array<string>
     */
    public function getRegisteredAgents(): array
    {
        return array_keys($this->agents);
    }

    /**
     * Get a registered agent by name.
     */
    public function getAgent(string $name): ?AgentInterface
    {
        return $this->agents[$name] ?? null;
    }

    /**
     * Calculate the composite score for an agent.
     *
     * Formula: successRate * 0.6 + speedScore * 0.2 + costScore * 0.2
     */
    public function calculateScore(float $successRate, float $speedScore, float $costScore): float
    {
        return ($successRate * 0.6) + ($speedScore * 0.2) + ($costScore * 0.2);
    }

    // ─── Collaboration Methods ─────────────────────────────────────────

    /**
     * Dispatch a task to multiple agents working collaboratively.
     * Each agent processes the task and the results are merged.
     */
    public function dispatchCollaborative(AgentTask $task, array $agentNames): AgentResult
    {
        $results = [];
        $totalCost = 0.0;
        $totalTokens = 0;
        $outputs = [];
        $errors = [];

        foreach ($agentNames as $name) {
            $agent = $this->getAgent($name);
            if ($agent === null) {
                Log::warning("AgentOrchestrator: collaborative dispatch - agent [{$name}] not found");
                $errors[] = "Agent not found: {$name}";

                continue;
            }

            if (! $agent->canHandle($task->type)) {
                Log::warning("AgentOrchestrator: collaborative dispatch - agent [{$name}] cannot handle [{$task->type}]");
                $errors[] = "Agent [{$name}] cannot handle task type: {$task->type}";

                continue;
            }

            try {
                $result = $agent->execute($task, new AgentContext);
                $results[$name] = $result;
                $totalCost += $result->costUsd;
                $totalTokens += $result->tokensUsed;

                if ($result->success) {
                    $outputs[$name] = $result->output;
                } else {
                    $errors[] = "[{$name}] {$result->error}";
                }
            } catch (\Exception $e) {
                $errors[] = "[{$name}] {$e->getMessage()}";
            }
        }

        // Merge outputs from successful agents
        $mergedOutput = $this->mergeCollaborativeOutputs($outputs, $task->type);
        $allSuccessful = ! empty($results) && count(array_filter($results, fn ($r) => $r->success)) === count($results);
        $anySuccessful = ! empty(array_filter($results, fn ($r) => $r->success));

        return new AgentResult(
            taskId: $task->id,
            agentName: 'collaborative:'.implode(',', $agentNames),
            success: $anySuccessful,
            output: $mergedOutput,
            costUsd: $totalCost,
            tokensUsed: $totalTokens,
            executionTimeMs: 0,
            error: $anySuccessful ? null : implode('; ', $errors),
            metadata: [
                'collaborative' => true,
                'agents_involved' => $agentNames,
                'individual_results' => array_map(fn ($r) => $r->toArray(), $results),
                'success_count' => count(array_filter($results, fn ($r) => $r->success)),
                'total_agents' => count($agentNames),
            ],
            timestamp: time(),
        );
    }

    /**
     * Get collaboration history for an agency.
     */
    public function getCollaborationHistory(int $agencyId): array
    {
        $history = $this->memory->getHistory();

        // Filter for collaborative tasks
        $collaborative = array_filter($history, function ($entry) {
            return str_starts_with($entry['agent_name'] ?? '', 'collaborative:');
        });

        return array_values($collaborative);
    }

    /**
     * Get shared knowledge report for an agency.
     */
    public function getSharedKnowledgeReport(int $agencyId): array
    {
        $history = $this->memory->getHistory();

        // Analyze collaboration patterns
        $collaborativeHistory = array_filter($history, function ($entry) {
            return str_starts_with($entry['agent_name'] ?? '', 'collaborative:');
        });

        $categoryStats = [];
        $agentCollaborationCount = [];

        foreach ($collaborativeHistory as $entry) {
            $agentName = $entry['agent_name'];
            $categoryStats[$agentName] = ($categoryStats[$agentName] ?? 0) + 1;

            // Extract individual agents from collaborative name
            if (str_starts_with($agentName, 'collaborative:')) {
                $agents = substr($agentName, 13);
                foreach (explode(',', $agents) as $a) {
                    $agentCollaborationCount[trim($a)] = ($agentCollaborationCount[trim($a)] ?? 0) + 1;
                }
            }
        }

        return [
            'total_collaborations' => count($collaborativeHistory),
            'collaboration_by_type' => $categoryStats,
            'agent_collaboration_frequency' => $agentCollaborationCount,
            'most_collaborative_agent' => ! empty($agentCollaborationCount) ? array_key_first(collect($agentCollaborationCount)->sortDesc()->toArray()) : null,
        ];
    }

    /**
     * Merge outputs from multiple collaborative agents.
     */
    private function mergeCollaborativeOutputs(array $outputs, string $taskType): string
    {
        if (empty($outputs)) {
            return '';
        }

        if (count($outputs) === 1) {
            return reset($outputs);
        }

        $merged = "=== Collaborative Results ({$taskType}) ===\n\n";
        foreach ($outputs as $agentName => $output) {
            $merged .= "--- {$agentName} ---\n{$output}\n\n";
        }

        return $merged;
    }
}
