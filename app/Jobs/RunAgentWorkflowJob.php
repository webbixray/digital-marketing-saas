<?php

namespace App\Jobs;

use App\Events\AgentWorkflowCompleted;
use App\Models\AgentWorkflowExecution;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAgentWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly string $workflowName,
        public readonly string $executionId,
        public readonly int $agencyId,
        public readonly int $userId,
        public readonly array $input = [],
    ) {}

    public function handle(AgentOrchestrator $orchestrator): void
    {
        $execution = AgentWorkflowExecution::where('execution_id', $this->executionId)->first();

        if ($execution === null) {
            Log::error("RunAgentWorkflowJob: execution [{$this->executionId}] not found");

            return;
        }

        if ($execution->status === 'cancelled') {
            Log::info("RunAgentWorkflowJob: execution [{$this->executionId}] was cancelled, skipping");

            return;
        }

        // Mark as running
        $execution->update(['status' => 'running']);

        try {
            $workflow = $this->getWorkflowDefinition($this->workflowName);

            if ($workflow === null) {
                throw new \RuntimeException("Workflow definition [{$this->workflowName}] not found");
            }

            // Build context
            $context = new AgentContext(
                agencyId: $this->agencyId,
                userId: $this->userId,
                tenantId: (string) $this->agencyId,
            );

            // Build tasks
            $tasks = $this->buildTasks($workflow, $this->input);

            $totalSteps = count($tasks);
            $completedSteps = 0;
            $results = [];

            // Execute each step individually for progress tracking
            $previousOutput = null;
            foreach ($tasks as $index => $task) {
                // Check for cancellation before each step
                $execution->refresh();
                if ($execution->status === 'cancelled') {
                    Log::info("RunAgentWorkflowJob: execution [{$this->executionId}] cancelled at step {$index}");

                    return;
                }

                // Enrich with previous output
                if ($previousOutput !== null) {
                    $task = $task->withPreviousOutput($previousOutput);
                }

                Log::info("RunAgentWorkflowJob: executing step [{$index}] of [{$this->workflowName}] for execution [{$this->executionId}]");

                $result = $orchestrator->dispatch($task, $context);
                $results[] = $result;
                $completedSteps++;

                // Update progress
                $execution->update([
                    'steps_completed' => $completedSteps,
                    'output_data' => [
                        'results' => array_map(fn ($r) => $r->toArray(), $results),
                    ],
                ]);

                Log::info("RunAgentWorkflowJob: step [{$index}] completed, success: ".($result->success ? 'yes' : 'no'));

                if (! $result->success) {
                    // Mark as failed
                    $execution->update([
                        'status' => 'failed',
                        'error_message' => $result->error,
                        'completed_at' => now(),
                        'duration_ms' => $this->calculateDuration($execution),
                    ]);

                    // Fire failure event
                    event(new AgentWorkflowCompleted(
                        execution: $execution,
                        workflowName: $this->workflowName,
                        agencyId: $this->agencyId,
                        userId: $this->userId,
                        success: false,
                        results: array_map(fn ($r) => $r->toArray(), $results),
                        errorMessage: $result->error,
                    ));

                    return;
                }

                $previousOutput = $result->output;
            }

            // All steps completed successfully
            $execution->update([
                'status' => 'success',
                'steps_completed' => $totalSteps,
                'output_data' => [
                    'results' => array_map(fn ($r) => $r->toArray(), $results),
                ],
                'completed_at' => now(),
                'duration_ms' => $this->calculateDuration($execution),
            ]);

            Log::info("RunAgentWorkflowJob: workflow [{$this->workflowName}] completed successfully for execution [{$this->executionId}]");

            // Fire completion event
            event(new AgentWorkflowCompleted(
                execution: $execution->fresh(),
                workflowName: $this->workflowName,
                agencyId: $this->agencyId,
                userId: $this->userId,
                success: true,
                results: array_map(fn ($r) => $r->toArray(), $results),
            ));

        } catch (\Exception $e) {
            Log::error("RunAgentWorkflowJob: workflow [{$this->workflowName}] failed: {$e->getMessage()}");

            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'duration_ms' => $this->calculateDuration($execution),
            ]);

            event(new AgentWorkflowCompleted(
                execution: $execution->fresh(),
                workflowName: $this->workflowName,
                agencyId: $this->agencyId,
                userId: $this->userId,
                success: false,
                errorMessage: $e->getMessage(),
            ));
        }
    }

    /**
     * Build tasks from workflow definition.
     *
     * @return array<AgentTask>
     */
    private function buildTasks(array $workflow, array $input): array
    {
        $tasks = [];
        foreach ($workflow['steps'] as $index => $step) {
            $tasks[] = new AgentTask(
                id: "{$this->executionId}_step_{$index}",
                type: $step['type'],
                prompt: $step['prompt'],
                data: $input,
                metadata: [
                    'execution_id' => $this->executionId,
                    'step_index' => $index,
                    'workflow_name' => $workflow['name'],
                ],
            );
        }

        return $tasks;
    }

    /**
     * Get workflow definition by name.
     */
    private function getWorkflowDefinition(string $name): ?array
    {
        $workflows = [
            'content_campaign' => [
                'name' => 'content_campaign',
                'steps' => [
                    ['type' => 'content_generate', 'prompt' => 'Generate campaign content based on input'],
                    ['type' => 'hashtag_generate', 'prompt' => 'Generate relevant hashtags for the content'],
                    ['type' => 'performance_analysis', 'prompt' => 'Analyze predicted performance'],
                ],
            ],
            'competitor_analysis' => [
                'name' => 'competitor_analysis',
                'steps' => [
                    ['type' => 'competitor_analysis', 'prompt' => 'Analyze competitor data'],
                    ['type' => 'trend_detection', 'prompt' => 'Detect market trends'],
                    ['type' => 'recommendation', 'prompt' => 'Generate strategic recommendations'],
                ],
            ],
            'security_audit' => [
                'name' => 'security_audit',
                'steps' => [
                    ['type' => 'security_audit', 'prompt' => 'Perform security audit'],
                    ['type' => 'vulnerability_scan', 'prompt' => 'Scan for vulnerabilities'],
                    ['type' => 'recommendation', 'prompt' => 'Generate security recommendations'],
                ],
            ],
            'content_optimization' => [
                'name' => 'content_optimization',
                'steps' => [
                    ['type' => 'content_optimize', 'prompt' => 'Optimize content for engagement'],
                    ['type' => 'content_rewrite', 'prompt' => 'Rewrite weak sections'],
                    ['type' => 'hashtag_generate', 'prompt' => 'Generate optimized hashtags'],
                ],
            ],
            'trend_report' => [
                'name' => 'trend_report',
                'steps' => [
                    ['type' => 'trend_detection', 'prompt' => 'Detect current trends'],
                    ['type' => 'performance_analysis', 'prompt' => 'Analyze trend performance'],
                    ['type' => 'recommendation', 'prompt' => 'Generate trend-based recommendations'],
                ],
            ],
        ];

        return $workflows[$name] ?? null;
    }

    /**
     * Calculate duration in milliseconds.
     */
    private function calculateDuration(AgentWorkflowExecution $execution): int
    {
        if ($execution->started_at === null) {
            return 0;
        }
        $end = $execution->completed_at ?? now();

        return (int) ($end->getTimestampMs() - $execution->started_at->getTimestampMs());
    }
}
