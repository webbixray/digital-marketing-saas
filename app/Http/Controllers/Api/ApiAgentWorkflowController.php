<?php

namespace App\Http\Controllers\Api;

use App\Concerns\StructuredLogger;
use App\Http\Controllers\Controller;
use App\Jobs\RunAgentWorkflowJob;
use App\Models\AgentWorkflowExecution;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiAgentWorkflowController extends Controller
{
    use StructuredLogger;

    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * GET /api/v1/agent-workflows - List available workflow templates.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $workflows = $this->getWorkflowTemplates();

            return response()->json([
                'data' => $workflows,
                'meta' => [
                    'total' => count($workflows),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_list_workflows_failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflows.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/agent-workflows/{name}/run - Execute a workflow.
     */
    public function run(Request $request, string $name): JsonResponse
    {
        try {
            $validated = $request->validate([
                'input' => 'sometimes|array',
                'async' => 'sometimes|boolean',
            ]);

            $workflows = $this->getWorkflowTemplates();
            $workflow = collect($workflows)->firstWhere('name', $name);

            if ($workflow === null) {
                return response()->json([
                    'error' => "Workflow [{$name}] not found.",
                    'available' => array_column($workflows, 'name'),
                ], 404);
            }

            // Check if required features are enabled
            $missingFeatures = $this->checkRequiredFeatures($workflow['required_features'], $request->user()->agency_id);
            if (! empty($missingFeatures)) {
                return response()->json([
                    'error' => 'Missing required features.',
                    'missing_features' => $missingFeatures,
                ], 403);
            }

            $executionId = 'wf_'.uniqid();
            $agencyId = $request->user()->agency_id;
            $userId = $request->user()->id;
            $input = $validated['input'] ?? [];
            $async = $validated['async'] ?? true;

            // Create execution record
            $execution = AgentWorkflowExecution::create([
                'execution_id' => $executionId,
                'workflow_name' => $name,
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'status' => 'pending',
                'input_data' => $input,
                'steps_total' => count($workflow['steps']),
                'steps_completed' => 0,
                'started_at' => now(),
            ]);

            if ($async) {
                // Dispatch async job
                RunAgentWorkflowJob::dispatch(
                    workflowName: $name,
                    executionId: $executionId,
                    agencyId: $agencyId,
                    userId: $userId,
                    input: $input,
                );

                $this->logAgentExecution('workflow_dispatched_async', [
                    'agency_id' => $agencyId,
                    'workflow_name' => $name,
                    'execution_id' => $executionId,
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'data' => [
                        'execution_id' => $executionId,
                        'workflow_name' => $name,
                        'status' => 'pending',
                        'message' => 'Workflow dispatched for async execution.',
                    ],
                ], 202);
            }

            // Synchronous execution
            try {
                $context = AgentContext::fromUser($request->user());
                $tasks = $this->buildTasksFromWorkflow($workflow, $input, $executionId);

                $results = $this->orchestrator->dispatchWorkflow($tasks, $context);

                // Update execution record
                $allSuccess = collect($results)->every(fn ($r) => $r->success);
                $execution->update([
                    'status' => $allSuccess ? 'success' : 'failed',
                    'steps_completed' => count($results),
                    'output_data' => [
                        'results' => array_map(fn ($r) => $r->toArray(), $results),
                    ],
                    'completed_at' => now(),
                ]);

                $this->logAgentExecution('workflow_completed', [
                    'agency_id' => $agencyId,
                    'workflow_name' => $name,
                    'execution_id' => $executionId,
                    'success' => $allSuccess,
                    'steps_completed' => count($results),
                ]);

                return response()->json([
                    'data' => [
                        'execution_id' => $executionId,
                        'workflow_name' => $name,
                        'status' => $allSuccess ? 'success' : 'failed',
                        'results' => array_map(fn ($r) => $r->toArray(), $results),
                    ],
                ]);
            } catch (\Exception $e) {
                $execution->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);

                $this->logAgentError('workflow_sync_execution_failed', [
                    'agency_id' => $agencyId,
                    'workflow_name' => $name,
                    'execution_id' => $executionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Workflow execution failed.',
                ], 500);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->logAgentError('api_run_workflow_failed', [
                'workflow_name' => $name,
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute workflow.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/agent-workflows/{name}/status/{executionId} - Check workflow status.
     */
    public function status(Request $request, string $name, string $executionId): JsonResponse
    {
        try {
            $execution = AgentWorkflowExecution::where('execution_id', $executionId)
                ->where('workflow_name', $name)
                ->where('agency_id', $request->user()->agency_id)
                ->first();

            if ($execution === null) {
                return response()->json([
                    'error' => "Execution [{$executionId}] not found for workflow [{$name}].",
                ], 404);
            }

            return response()->json([
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
            $this->logAgentError('api_workflow_status_failed', [
                'workflow_name' => $name,
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflow status.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/agent-workflows/{name}/status/{executionId} - Cancel workflow.
     */
    public function cancel(Request $request, string $name, string $executionId): JsonResponse
    {
        try {
            $execution = AgentWorkflowExecution::where('execution_id', $executionId)
                ->where('workflow_name', $name)
                ->where('agency_id', $request->user()->agency_id)
                ->first();

            if ($execution === null) {
                return response()->json([
                    'error' => "Execution [{$executionId}] not found for workflow [{$name}].",
                ], 404);
            }

            if (in_array($execution->status, ['success', 'failed', 'cancelled'])) {
                return response()->json([
                    'error' => "Cannot cancel workflow with status [{$execution->status}].",
                    'execution_id' => $executionId,
                    'current_status' => $execution->status,
                ], 409);
            }

            $execution->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error_message' => 'Cancelled by user',
            ]);

            $this->logAgentExecution('workflow_cancelled', [
                'agency_id' => $request->user()->agency_id,
                'workflow_name' => $name,
                'execution_id' => $executionId,
            ]);

            return response()->json([
                'data' => [
                    'execution_id' => $executionId,
                    'workflow_name' => $name,
                    'status' => 'cancelled',
                    'message' => 'Workflow cancelled successfully.',
                ],
            ]);
        } catch (\Exception $e) {
            $this->logAgentError('api_cancel_workflow_failed', [
                'workflow_name' => $name,
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel workflow.',
            ], 500);
        }
    }

    /**
     * Get available workflow templates.
     */
    private function getWorkflowTemplates(): array
    {
        return [
            [
                'name' => 'content_campaign',
                'description' => 'Generate a full content campaign with posts, hashtags, and scheduling recommendations.',
                'required_features' => ['ai_content', 'social_posting'],
                'steps' => [
                    ['type' => 'content_generate', 'prompt' => 'Generate campaign content based on input'],
                    ['type' => 'hashtag_generate', 'prompt' => 'Generate relevant hashtags for the content'],
                    ['type' => 'performance_analysis', 'prompt' => 'Analyze predicted performance'],
                ],
            ],
            [
                'name' => 'competitor_analysis',
                'description' => 'Analyze competitors and generate strategic recommendations.',
                'required_features' => ['analytics', 'ai_content'],
                'steps' => [
                    ['type' => 'competitor_analysis', 'prompt' => 'Analyze competitor data'],
                    ['type' => 'trend_detection', 'prompt' => 'Detect market trends'],
                    ['type' => 'recommendation', 'prompt' => 'Generate strategic recommendations'],
                ],
            ],
            [
                'name' => 'security_audit',
                'description' => 'Run a comprehensive security audit on accounts and content.',
                'required_features' => ['security'],
                'steps' => [
                    ['type' => 'security_audit', 'prompt' => 'Perform security audit'],
                    ['type' => 'vulnerability_scan', 'prompt' => 'Scan for vulnerabilities'],
                    ['type' => 'recommendation', 'prompt' => 'Generate security recommendations'],
                ],
            ],
            [
                'name' => 'content_optimization',
                'description' => 'Optimize existing content for better engagement.',
                'required_features' => ['ai_content'],
                'steps' => [
                    ['type' => 'content_optimize', 'prompt' => 'Optimize content for engagement'],
                    ['type' => 'content_rewrite', 'prompt' => 'Rewrite weak sections'],
                    ['type' => 'hashtag_generate', 'prompt' => 'Generate optimized hashtags'],
                ],
            ],
            [
                'name' => 'trend_report',
                'description' => 'Generate a trend analysis report with actionable insights.',
                'required_features' => ['analytics'],
                'steps' => [
                    ['type' => 'trend_detection', 'prompt' => 'Detect current trends'],
                    ['type' => 'performance_analysis', 'prompt' => 'Analyze trend performance'],
                    ['type' => 'recommendation', 'prompt' => 'Generate trend-based recommendations'],
                ],
            ],
        ];
    }

    /**
     * Check if agency has required features enabled.
     */
    private function checkRequiredFeatures(array $requiredFeatures, int $agencyId): array
    {
        // In a real implementation, this would check against agency plan features
        // For now, return empty (all features available)
        return [];
    }

    /**
     * Build AgentTask objects from workflow template.
     *
     * @return array<AgentTask>
     */
    private function buildTasksFromWorkflow(array $workflow, array $input, string $executionId): array
    {
        $tasks = [];
        foreach ($workflow['steps'] as $index => $step) {
            $tasks[] = new AgentTask(
                id: "{$executionId}_step_{$index}",
                type: $step['type'],
                prompt: $step['prompt'],
                data: $input,
                metadata: [
                    'execution_id' => $executionId,
                    'step_index' => $index,
                    'workflow_name' => $workflow['name'],
                ],
            );
        }

        return $tasks;
    }
}
