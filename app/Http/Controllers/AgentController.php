<?php

namespace App\Http\Controllers;

use App\Concerns\StructuredLogger;
use App\Jobs\RunAgentWorkflowJob;
use App\Models\AgentCostLog;
use App\Models\AgentWorkflowExecution;
use App\Services\AgentRegistry;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentCostTracker;
use App\Services\AI\Agent\AgentHealthMonitor;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AgentController extends Controller
{
    use StructuredLogger;

    public function __construct(
        private AgentOrchestrator $orchestrator,
        private AgentHealthMonitor $healthMonitor,
        private AgentCostTracker $costTracker,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Agent dashboard page with agent cards, health score, costs, and activity.
     */
    public function dashboard(Request $request)
    {
        try {
            $agency = $request->user()->agency;
            $agencyId = $request->user()->agency_id;

            // Get agent stats from orchestrator
            $agentStats = $this->orchestrator->getAgentStats();

            // Enhance with health data and status
            $agents = [];
            foreach ($agentStats as $name => $stat) {
                $agent = $this->orchestrator->getAgent($name);
                $health = $agent ? $this->healthMonitor->checkAgentHealth($agent) : null;

                $agents[$name] = array_merge($stat, [
                    'status' => $health['status'] ?? 'active',
                    'last_run' => $health['last_execution'] ?? 'Never',
                    'description' => AgentRegistry::getDescription($name),
                    'category' => AgentRegistry::getCategory($name),
                ]);
            }

            // System health score
            $agentHealth = $this->healthMonitor->getSystemHealth();

            // Cost summary
            $costSummary = [
                'total' => $this->costTracker->getMonthlyCost($agencyId),
                'by_agent' => $this->costTracker->getCostByAgent($agencyId),
            ];

            // Budget info
            $budgetLimit = $this->costTracker->getBudgetLimit($agencyId);
            $budgetRemaining = $this->costTracker->getRemainingBudget($agencyId);

            // Recent activity (last 10 agent executions)
            $recentActivity = AgentCostLog::byAgency($agencyId)
                ->orderBy('executed_at', 'desc')
                ->take(10)
                ->get();

            return view('agents.dashboard', compact(
                'agents',
                'agentHealth',
                'costSummary',
                'budgetLimit',
                'budgetRemaining',
                'recentActivity',
            ));
        } catch (\Exception $e) {
            Log::error('Failed to load agent dashboard', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to load agent dashboard. Please try again.');
        }
    }

    /**
     * Single agent detail page.
     */
    public function agentDetail(Request $request, string $agentName)
    {
        try {
            $agencyId = $request->user()->agency_id;

            $agents = $this->orchestrator->getAgentStats();

            if (! isset($agents[$agentName])) {
                abort(404);
            }

            $agent = $agents[$agentName];
            $agentObj = $this->orchestrator->getAgent($agentName);

            // Health check for status
            $health = $agentObj ? $this->healthMonitor->checkAgentHealth($agentObj) : null;
            $agent['status'] = $health['status'] ?? 'active';
            $agent['description'] = AgentRegistry::getDescription($agentName);
            $agent['category'] = AgentRegistry::getCategory($agentName);

            // Learned patterns from storage
            $learnedPatterns = [];
            $memoryPath = "agent_memory/global/{$agentName}.json";
            try {
                if (Storage::exists($memoryPath)) {
                    $data = json_decode(Storage::get($memoryPath), true);
                    $patterns = $data['stats'] ?? [];
                    foreach ($patterns as $key => $value) {
                        if (is_array($value) && ! empty($value)) {
                            $learnedPatterns[] = ucfirst(str_replace('_', ' ', $key)).': '.count($value).' samples collected';
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to load agent memory for {$agentName}", [
                    'error' => $e->getMessage(),
                ]);
            }

            // Recent executions for this agent
            $executions = AgentCostLog::byAgency($agencyId)
                ->byAgent($agentName)
                ->orderBy('executed_at', 'desc')
                ->take(20)
                ->get();

            return view('agents.show', compact('agent', 'agentName', 'learnedPatterns', 'executions'));
        } catch (\Exception $e) {
            Log::error('Failed to load agent detail', [
                'agent_name' => $agentName,
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to load agent details. Please try again.');
        }
    }

    /**
     * Workflows page.
     */
    public function workflows(Request $request)
    {
        try {
            $agencyId = $request->user()->agency_id;

            // Get workflow data
            $workflows = $this->listWorkflows()->getData(true)['data'];

            // Execution history
            $executions = AgentWorkflowExecution::where('agency_id', $agencyId)
                ->orderBy('started_at', 'desc')
                ->take(50)
                ->get();

            return view('agents.workflows', compact('workflows', 'executions'));
        } catch (\Exception $e) {
            Log::error('Failed to load workflows page', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to load workflows. Please try again.');
        }
    }

    /**
     * List all registered agents with stats.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $agents = $this->orchestrator->getAgentStats();

            return response()->json([
                'success' => true,
                'data' => $agents,
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('list_agents_failed', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agents.',
            ], 500);
        }
    }

    /**
     * Show agent details and learned patterns.
     */
    public function show(Request $request, string $agentName): JsonResponse
    {
        try {
            $agents = $this->orchestrator->getAgentStats();

            if (! isset($agents[$agentName])) {
                return response()->json([
                    'success' => false,
                    'message' => "Agent '{$agentName}' not found",
                ], 404);
            }

            $agentData = $agents[$agentName];

            // Get learned patterns from storage if available
            $memoryPath = "agent_memory/global/{$agentName}.json";
            $learnedPatterns = [];

            try {
                if (Storage::exists($memoryPath)) {
                    $data = json_decode(Storage::get($memoryPath), true);
                    $learnedPatterns = $data['stats'] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to load agent memory for {$agentName}", [
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'agent' => $agentData,
                    'learned_patterns' => $learnedPatterns,
                    'registered_at' => $agentData['total_executed'] > 0 ? 'active' : 'idle',
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('show_agent_failed', [
                'agent_name' => $agentName,
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agent details.',
            ], 500);
        }
    }

    /**
     * Manually dispatch a task to an agent.
     */
    public function dispatch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'agent_name' => 'required|string',
                'task_type' => 'required|string|in:content_generate,content_optimize,content_rewrite,hashtag_generate,performance_analysis,trend_detection,competitor_analysis,recommendation,security_audit,input_scan,auth_check,vulnerability_scan',
                'prompt' => 'required|string|max:10000',
                'data' => 'nullable|array',
            ]);

            $task = new AgentTask(
                id: uniqid('task_', true),
                type: $validated['task_type'],
                prompt: $validated['prompt'],
                data: $validated['data'] ?? [],
                preferredAgent: $validated['agent_name'],
            );

            $context = AgentContext::fromUser($request->user());
            $result = $this->orchestrator->dispatch($task, $context);

            $this->logAgentExecution('task_dispatched', [
                'agency_id' => $request->user()->agency_id,
                'agent_name' => $validated['agent_name'],
                'task_type' => $validated['task_type'],
                'task_id' => $task->id,
                'success' => $result->success,
                'cost_usd' => $result->costUsd,
            ]);

            if (! $result->success) {
                return response()->json([
                    'success' => false,
                    'message' => $result->error ?? 'Task failed',
                    'agent' => $result->agentName,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->logAgentError('dispatch_failed', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch task. Please try again.',
            ], 500);
        }
    }

    /**
     * Overall orchestration statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $agents = $this->orchestrator->getAgentStats();

            $totalExecuted = 0;
            $totalCost = 0.0;
            $totalSuccesses = 0;

            foreach ($agents as $agent) {
                $totalExecuted += $agent['total_executed'];
                $totalCost += $agent['total_cost'];
                $totalSuccesses += $agent['total_successes'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_agents' => count($agents),
                    'total_executed' => $totalExecuted,
                    'total_successes' => $totalSuccesses,
                    'overall_success_rate' => $totalExecuted > 0
                        ? round($totalSuccesses / $totalExecuted, 4)
                        : 0.0,
                    'total_cost_usd' => round($totalCost, 6),
                    'agents' => $agents,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('stats_failed', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
            ], 500);
        }
    }

    /**
     * Execute a named workflow.
     */
    public function runWorkflow(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'workflow_name' => 'required|string|in:content_campaign,competitor_analysis,security_audit,content_optimization,trend_report',
                'input' => 'sometimes|array',
                'async' => 'sometimes|boolean',
            ]);

            $workflowName = $validated['workflow_name'];
            $input = $validated['input'] ?? [];
            $async = $validated['async'] ?? true;

            $executionId = 'wf_'.uniqid();
            $agencyId = $request->user()->agency_id;
            $userId = $request->user()->id;

            // Create execution record
            $execution = AgentWorkflowExecution::create([
                'execution_id' => $executionId,
                'workflow_name' => $workflowName,
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'status' => 'pending',
                'input_data' => $input,
                'steps_total' => 3,
                'steps_completed' => 0,
                'started_at' => now(),
            ]);

            if ($async) {
                RunAgentWorkflowJob::dispatch(
                    workflowName: $workflowName,
                    executionId: $executionId,
                    agencyId: $agencyId,
                    userId: $userId,
                    input: $input,
                );

                $this->logAgentExecution('workflow_dispatched_async', [
                    'agency_id' => $agencyId,
                    'workflow_name' => $workflowName,
                    'execution_id' => $executionId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'execution_id' => $executionId,
                        'workflow_name' => $workflowName,
                        'status' => 'pending',
                        'message' => 'Workflow dispatched for async execution.',
                    ],
                ], 202);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->logAgentError('workflow_failed', [
                'agency_id' => $request->user()->agency_id,
                'workflow_name' => $validated['workflow_name'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute workflow. Please try again.',
            ], 500);
        }
    }

    /**
     * List available workflows with required features.
     */
    public function listWorkflows(): JsonResponse
    {
        try {
            $workflows = [
                [
                    'name' => 'content_campaign',
                    'description' => 'Generate a full content campaign with posts, hashtags, and scheduling recommendations.',
                    'required_features' => ['ai_content', 'social_posting'],
                    'steps' => ['content_generate', 'hashtag_generate', 'performance_analysis'],
                ],
                [
                    'name' => 'competitor_analysis',
                    'description' => 'Analyze competitors and generate strategic recommendations.',
                    'required_features' => ['analytics', 'ai_content'],
                    'steps' => ['competitor_analysis', 'trend_detection', 'recommendation'],
                ],
                [
                    'name' => 'security_audit',
                    'description' => 'Run a comprehensive security audit on accounts and content.',
                    'required_features' => ['security'],
                    'steps' => ['security_audit', 'vulnerability_scan', 'recommendation'],
                ],
                [
                    'name' => 'content_optimization',
                    'description' => 'Optimize existing content for better engagement.',
                    'required_features' => ['ai_content'],
                    'steps' => ['content_optimize', 'content_rewrite', 'hashtag_generate'],
                ],
                [
                    'name' => 'trend_report',
                    'description' => 'Generate a trend analysis report with actionable insights.',
                    'required_features' => ['analytics'],
                    'steps' => ['trend_detection', 'performance_analysis', 'recommendation'],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $workflows,
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('list_workflows_failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflows.',
            ], 500);
        }
    }

    /**
     * Check workflow progress.
     */
    public function getWorkflowStatus(string $executionId): JsonResponse
    {
        try {
            $execution = AgentWorkflowExecution::where('execution_id', $executionId)->first();

            if ($execution === null) {
                return response()->json([
                    'success' => false,
                    'message' => "Workflow execution [{$executionId}] not found.",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'execution_id' => $execution->execution_id,
                    'workflow_name' => $execution->workflow_name,
                    'status' => $execution->status,
                    'steps_total' => $execution->steps_total,
                    'steps_completed' => $execution->steps_completed,
                    'progress_percentage' => $execution->steps_total > 0
                        ? round(($execution->steps_completed / $execution->steps_total) * 100, 1)
                        : 0,
                    'input_data' => $execution->input_data,
                    'output_data' => $execution->output_data,
                    'error_message' => $execution->error_message,
                    'started_at' => $execution->started_at?->toIso8601String(),
                    'completed_at' => $execution->completed_at?->toIso8601String(),
                    'duration_ms' => $execution->duration_ms,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('get_workflow_status_failed', [
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflow status.',
            ], 500);
        }
    }

}
