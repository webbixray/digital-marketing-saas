<?php

namespace App\Http\Controllers\Api;

use App\Concerns\StructuredLogger;
use App\Http\Controllers\Controller;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentHealthMonitor;
use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentMemory;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiAgentController extends Controller
{
    use StructuredLogger;

    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
        private readonly AgentHealthMonitor $healthMonitor,
        private readonly AgentMemory $memory,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * GET /api/v1/agents - List all agents with health status.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $stats = $this->orchestrator->getAgentStats();
            $agents = [];

            foreach ($stats as $name => $stat) {
                $health = $this->healthMonitor->checkAgentHealth(
                    $this->getAgentByName($name)
                );

                $agents[] = [
                    'name' => $name,
                    'status' => $health['status'],
                    'success_rate' => $stat['success_rate'],
                    'total_executed' => $stat['total_executed'],
                    'total_cost' => $stat['total_cost'],
                    'avg_cost_per_task' => $stat['avg_cost_per_task'],
                    'speed_score' => $stat['speed_score'],
                    'cost_score' => $stat['cost_score'],
                    'supported_types' => $stat['supported_types'],
                    'uptime_percentage' => $health['uptime_percentage'],
                    'last_execution' => $health['last_execution'],
                    'error_rate' => $health['error_rate'],
                ];
            }

            return response()->json([
                'data' => $agents,
                'meta' => [
                    'total' => count($agents),
                    'checked_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_list_agents_failed', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agents.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/agents/{name} - Agent details and stats.
     */
    public function show(string $name): JsonResponse
    {
        try {
            $agent = $this->getAgentByName($name);

            if ($agent === null) {
                return response()->json([
                    'error' => "Agent [{$name}] not found.",
                ], 404);
            }

            $stats = $this->orchestrator->getAgentStats();
            $stat = $stats[$name] ?? null;

            if ($stat === null) {
                return response()->json([
                    'error' => "No stats available for agent [{$name}].",
                ], 404);
            }

            $health = $this->healthMonitor->checkAgentHealth($agent);
            $history = $this->getAgentHistory($name);

            return response()->json([
                'data' => [
                    'name' => $name,
                    'status' => $health['status'],
                    'success_rate' => $stat['success_rate'],
                    'total_executed' => $stat['total_executed'],
                    'total_successes' => $stat['total_successes'],
                    'total_cost' => $stat['total_cost'],
                    'avg_cost_per_task' => $stat['avg_cost_per_task'],
                    'speed_score' => $stat['speed_score'],
                    'cost_score' => $stat['cost_score'],
                    'supported_types' => $stat['supported_types'],
                    'uptime_percentage' => $health['uptime_percentage'],
                    'error_rate' => $health['error_rate'],
                    'last_execution' => $health['last_execution'],
                    'last_error' => $health['last_error'],
                    'recent_history' => $history,
                    'checked_at' => $health['checked_at'],
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_show_agent_failed', [
                'agent_name' => $name,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agent details.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/agents/{name}/dispatch - Dispatch task to agent.
     */
    public function dispatch(Request $request, string $name): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_type' => 'required|string|max:100',
                'prompt' => 'required|string|max:10000',
                'data' => 'sometimes|array',
                'preferred_agent' => 'sometimes|string',
                'max_retries' => 'sometimes|integer|min:0|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $agent = $this->getAgentByName($name);

            if ($agent === null) {
                return response()->json([
                    'error' => "Agent [{$name}] not found.",
                ], 404);
            }

            $taskType = $request->input('task_type');

            if (! $agent->canHandle($taskType)) {
                return response()->json([
                    'error' => "Agent [{$name}] cannot handle task type [{$taskType}].",
                    'supported_types' => $agent->getSupportedTaskTypes(),
                ], 422);
            }

            $task = new AgentTask(
                id: 'task_'.uniqid(),
                type: $taskType,
                prompt: $request->input('prompt'),
                data: $request->input('data', []),
                preferredAgent: $request->input('preferred_agent'),
                maxRetries: $request->input('max_retries', 3),
            );

            $context = AgentContext::fromUser($request->user());

            try {
                $result = $this->orchestrator->dispatch($task, $context);

                // Record task execution for health monitoring
                $this->healthMonitor->recordTaskExecution($name, $result);

                $response = [
                    'data' => [
                        'task_id' => $result->taskId,
                        'agent_name' => $result->agentName,
                        'success' => $result->success,
                        'output' => $result->output,
                        'cost_usd' => $result->costUsd,
                        'tokens_used' => $result->tokensUsed,
                        'execution_time_ms' => $result->executionTimeMs,
                        'metadata' => $result->metadata,
                        'timestamp' => $result->timestamp,
                    ],
                ];

                if (! $result->success) {
                    $response['data']['error'] = $result->error;

                    $this->logAgentError('task_failed', [
                        'agent_name' => $name,
                        'task_id' => $task->id,
                        'error' => $result->error,
                    ]);

                    return response()->json($response, 200);
                }

                $this->logAgentExecution('task_completed', [
                    'agency_id' => $request->user()->agency_id,
                    'agent_name' => $name,
                    'task_id' => $task->id,
                    'task_type' => $taskType,
                    'cost_usd' => $result->costUsd,
                    'tokens_used' => $result->tokensUsed,
                    'execution_time_ms' => $result->executionTimeMs,
                ]);

                return response()->json($response);
            } catch (\Exception $e) {
                $this->logAgentError('dispatch_orchestrator_failed', [
                    'agent_name' => $name,
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->healthMonitor->sendAlert($name, "Dispatch failed: {$e->getMessage()}");

                return response()->json([
                    'error' => 'Task dispatch failed.',
                    'message' => config('app.debug') ? $e->getMessage() : 'An internal error occurred.',
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logAgentError('api_dispatch_failed', [
                'agent_name' => $name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch task.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/agents/{name}/history - Agent execution history.
     */
    public function history(Request $request, string $name): JsonResponse
    {
        try {
            $agent = $this->getAgentByName($name);

            if ($agent === null) {
                return response()->json([
                    'error' => "Agent [{$name}] not found.",
                ], 404);
            }

            $limit = min((int) $request->input('limit', 50), 100);
            $offset = max((int) $request->input('offset', 0), 0);

            $history = $this->getAgentHistory($name, $limit, $offset);

            return response()->json([
                'data' => $history,
                'meta' => [
                    'agent_name' => $name,
                    'total' => count($history),
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_agent_history_failed', [
                'agent_name' => $name,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agent history.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/agents/stats - Overall orchestration statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $agentStats = $this->orchestrator->getAgentStats();

            $totalExecuted = 0;
            $totalCost = 0.0;
            $totalSuccesses = 0;

            foreach ($agentStats as $stat) {
                $totalExecuted += $stat['total_executed'];
                $totalCost += $stat['total_cost'];
                $totalSuccesses += $stat['total_successes'];
            }

            $overallSuccessRate = $totalExecuted > 0 ? $totalSuccesses / $totalExecuted : 0.0;

            return response()->json([
                'data' => [
                    'total_agents' => count($agentStats),
                    'total_executed' => $totalExecuted,
                    'total_successes' => $totalSuccesses,
                    'total_failures' => $totalExecuted - $totalSuccesses,
                    'overall_success_rate' => round($overallSuccessRate, 4),
                    'total_cost' => round($totalCost, 4),
                    'average_cost_per_task' => $totalExecuted > 0 ? round($totalCost / $totalExecuted, 4) : 0.0,
                    'agents' => $agentStats,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_stats_failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/agents/health - System health check.
     */
    public function health(): JsonResponse
    {
        try {
            $systemHealth = $this->healthMonitor->getSystemHealth();

            return response()->json([
                'data' => $systemHealth,
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_health_check_failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check system health.',
            ], 500);
        }
    }

    /**
     * Get agent instance by name from orchestrator.
     */
    private function getAgentByName(string $name): ?AgentInterface
    {
        return $this->orchestrator->getAgent($name);
    }

    /**
     * Get agent execution history from memory.
     */
    private function getAgentHistory(string $name, int $limit = 50, int $offset = 0): array
    {
        try {
            $memory = $this->memory;
            $allHistory = $memory->getHistory();

            // Filter history for this agent
            $agentHistory = array_filter($allHistory, fn ($entry) => $entry['agent_name'] === $name);

            // Sort by timestamp descending (most recent first)
            usort($agentHistory, fn ($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

            // Apply offset and limit
            $agentHistory = array_slice($agentHistory, $offset, $limit);

            return array_values(array_map(fn ($entry) => [
                'task_type' => $entry['task_type'],
                'success' => $entry['success'],
                'cost_usd' => $entry['cost_usd'],
                'execution_time_ms' => $entry['execution_time_ms'],
                'timestamp' => $entry['timestamp'],
                'datetime' => date('Y-m-d H:i:s', $entry['timestamp']),
            ], $agentHistory));
        } catch (\Exception $e) {
            Log::warning("Failed to get agent history for {$name}", [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
