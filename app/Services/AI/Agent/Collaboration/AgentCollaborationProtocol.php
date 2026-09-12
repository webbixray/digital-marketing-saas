<?php

namespace App\Services\AI\Agent\Collaboration;

use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Support\Facades\Log;

class AgentCollaborationProtocol
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
        private readonly SharedKnowledgeBase $knowledgeBase,
    ) {}

    /**
     * Request help from another agent for a specific task.
     */
    public function requestHelp(AgentInterface $from, string $toAgent, AgentTask $task): AgentResult
    {
        $targetAgent = $this->orchestrator->getAgent($toAgent);

        if ($targetAgent === null) {
            Log::warning("AgentCollaborationProtocol: agent [{$toAgent}] not found for help request from [{$from->getName()}]");

            return AgentResult::failure(
                taskId: $task->id,
                agentName: $toAgent,
                error: "Agent not found: {$toAgent}",
            );
        }

        if (! $targetAgent->canHandle($task->type)) {
            Log::warning("AgentCollaborationProtocol: agent [{$toAgent}] cannot handle task type [{$task->type}]");

            return AgentResult::failure(
                taskId: $task->id,
                agentName: $toAgent,
                error: "Agent [{$toAgent}] cannot handle task type: {$task->type}",
            );
        }

        Log::info("AgentCollaborationProtocol: agent [{$from->getName()}] requesting help from [{$toAgent}] for task [{$task->id}]");

        try {
            $result = $targetAgent->execute($task, new AgentContext);

            // Share the insight about collaboration
            $this->knowledgeBase->shareInsight(
                agencyId: 0,
                fromAgent: $from->getName(),
                insight: "Requested help from [{$toAgent}] for task type [{$task->type}], result: ".($result->success ? 'success' : 'failure'),
                category: 'collaboration',
            );

            return $result;
        } catch (\Exception $e) {
            Log::error("AgentCollaborationProtocol: help request failed: {$e->getMessage()}");

            return AgentResult::failure(
                taskId: $task->id,
                agentName: $toAgent,
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Share knowledge between agents.
     */
    public function shareKnowledge(string $fromAgent, string $toAgent, array $insights): void
    {
        foreach ($insights as $insight) {
            $category = is_array($insight) ? ($insight['category'] ?? 'general') : 'general';
            $content = is_array($insight) ? ($insight['content'] ?? json_encode($insight)) : $insight;

            $this->knowledgeBase->shareInsight(
                agencyId: 0,
                fromAgent: $fromAgent,
                insight: "[{$fromAgent} -> {$toAgent}] {$content}",
                category: $category,
            );
        }

        Log::info("AgentCollaborationProtocol: knowledge shared from [{$fromAgent}] to [{$toAgent}], ".count($insights).' insights');
    }

    /**
     * Returns all agents' capabilities.
     */
    public function getAgentCapabilities(): array
    {
        $capabilities = [];
        $agentNames = $this->orchestrator->getRegisteredAgents();

        foreach ($agentNames as $name) {
            $agent = $this->orchestrator->getAgent($name);
            if ($agent !== null) {
                $capabilities[$name] = [
                    'name' => $name,
                    'supported_types' => $agent->getSupportedTaskTypes(),
                    'success_rate' => $agent->getSuccessRate(),
                    'speed_score' => $agent->getSpeedScore(),
                    'cost_score' => $agent->getCostScore(),
                    'total_executed' => $agent->getTotalExecuted(),
                    'total_cost' => $agent->getTotalCost(),
                ];
            }
        }

        return $capabilities;
    }

    /**
     * Find agents that can help with a given task type.
     */
    public function findCollaborators(string $taskType): array
    {
        $collaborators = [];
        $agentNames = $this->orchestrator->getRegisteredAgents();

        foreach ($agentNames as $name) {
            $agent = $this->orchestrator->getAgent($name);
            if ($agent !== null && $agent->canHandle($taskType)) {
                $collaborators[$name] = [
                    'name' => $name,
                    'success_rate' => $agent->getSuccessRate(),
                    'speed_score' => $agent->getSpeedScore(),
                    'cost_score' => $agent->getCostScore(),
                    'supported_types' => $agent->getSupportedTaskTypes(),
                ];
            }
        }

        // Sort by success rate descending
        uasort($collaborators, fn ($a, $b) => $b['success_rate'] <=> $a['success_rate']);

        return $collaborators;
    }
}
