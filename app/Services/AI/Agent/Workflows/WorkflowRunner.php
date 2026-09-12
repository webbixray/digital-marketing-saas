<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;
use App\Models\User;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Support\Facades\Log;

class WorkflowRunner
{
    /**
     * @var array<string, AgentWorkflowTemplate>
     */
    private array $templates = [];

    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
    ) {}

    /**
     * Register a workflow template.
     */
    public function registerTemplate(AgentWorkflowTemplate $template): void
    {
        $this->templates[$template->getName()] = $template;
        Log::info("WorkflowRunner: registered template [{$template->getName()}]");
    }

    /**
     * Get a registered workflow template by name.
     */
    public function getTemplate(string $name): ?AgentWorkflowTemplate
    {
        return $this->templates[$name] ?? null;
    }

    /**
     * Get all registered workflow template names.
     *
     * @return array<string>
     */
    public function getRegisteredTemplates(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Check whether a template is registered.
     */
    public function hasTemplate(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    /**
     * Get all registered templates available for the given agency.
     *
     * @return array<AgentWorkflowTemplate>
     */
    public function getAvailableTemplates(Agency $agency): array
    {
        return array_filter($this->templates, fn (AgentWorkflowTemplate $template) => $template->isAvailable($agency));
    }

    /**
     * Execute a workflow template for a given agency and user.
     *
     * @return array{
     *     workflow_name: string,
     *     workflow_description: string,
     *     agency_id: int,
     *     user_id: int,
     *     total_steps: int,
     *     completed_steps: int,
     *     successful: bool,
     *     results: array<array{
     *         step: int,
     *         agent: string,
     *         task_type: string,
     *         success: bool,
     *         output: string,
     *         error: ?string,
     *         metadata: array,
     *     }>,
     *     started_at: string,
     *     completed_at: string,
     * }
     */
    public function run(AgentWorkflowTemplate $template, int $agencyId, int $userId): array
    {
        $startedAt = now()->toIso8601String();
        $tasks = $template->getTasks();
        $results = [];
        $successful = true;
        $completedSteps = 0;

        // Load agency and user for context
        $agency = Agency::find($agencyId);
        $user = User::find($userId);

        $context = new AgentContext(
            agency: $agency,
            user: $user,
            agencyId: $agencyId,
            userId: $userId,
            tenantId: (string) $agencyId,
        );

        foreach ($tasks as $index => $taskDef) {
            $agentName = $taskDef['agent'];
            $taskType = $taskDef['task_type'];
            $prompt = $taskDef['prompt'];
            $data = $taskDef['data'] ?? [];
            $metadata = $taskDef['metadata'] ?? [];

            // Get the specific agent for this step
            $agent = $this->orchestrator->getAgent($agentName);

            if ($agent === null) {
                Log::warning("WorkflowRunner: agent [{$agentName}] not found for step [{$index}]");
                $results[] = [
                    'step' => $index + 1,
                    'agent' => $agentName,
                    'task_type' => $taskType,
                    'success' => false,
                    'output' => '',
                    'error' => "Agent not found: {$agentName}",
                    'metadata' => $metadata,
                ];
                $successful = false;
                break;
            }

            // Build previous outputs for context chaining
            $previousOutputs = array_column(array_filter($results, fn ($r) => $r['success']), 'output');

            $task = new AgentTask(
                id: sprintf('workflow-%s-step-%d-%d', $template->getName(), $index + 1, $agencyId),
                type: $taskType,
                prompt: $prompt,
                data: $data,
                previousOutputs: $previousOutputs,
                preferredAgent: $agentName,
                metadata: $metadata,
            );

            try {
                $result = $agent->execute($task, $context);

                $results[] = [
                    'step' => $index + 1,
                    'agent' => $result->agentName,
                    'task_type' => $taskType,
                    'success' => $result->success,
                    'output' => $result->output,
                    'error' => $result->error,
                    'metadata' => $result->metadata,
                ];

                if ($result->success) {
                    $completedSteps++;
                } else {
                    $successful = false;
                    Log::warning("WorkflowRunner: step [{$index}] failed for workflow [{$template->getName()}]");
                    break;
                }
            } catch (\Exception $e) {
                Log::error("WorkflowRunner: exception at step [{$index}]: {$e->getMessage()}");
                $results[] = [
                    'step' => $index + 1,
                    'agent' => $agentName,
                    'task_type' => $taskType,
                    'success' => false,
                    'output' => '',
                    'error' => $e->getMessage(),
                    'metadata' => $metadata,
                ];
                $successful = false;
                break;
            }
        }

        return [
            'workflow_name' => $template->getName(),
            'workflow_description' => $template->getDescription(),
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'total_steps' => count($tasks),
            'completed_steps' => $completedSteps,
            'successful' => $successful,
            'results' => $results,
            'started_at' => $startedAt,
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
