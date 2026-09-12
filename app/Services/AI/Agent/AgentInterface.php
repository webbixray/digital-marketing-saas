<?php

namespace App\Services\AI\Agent;

interface AgentInterface
{
    /**
     * Get the unique name of this agent.
     */
    public function getName(): string;

    /**
     * Get the task types this agent supports.
     *
     * @return array<string>
     */
    public function getSupportedTaskTypes(): array;

    /**
     * Execute a task and return a result.
     */
    public function execute(AgentTask $task, AgentContext $context): AgentResult;

    /**
     * Get the success rate (0.0 to 1.0) for this agent.
     */
    public function getSuccessRate(): float;

    /**
     * Get the average execution speed score (0.0 to 1.0).
     * Higher is faster.
     */
    public function getSpeedScore(): float;

    /**
     * Get the cost efficiency score (0.0 to 1.0).
     * Higher is cheaper.
     */
    public function getCostScore(): float;

    /**
     * Get total number of tasks executed.
     */
    public function getTotalExecuted(): int;

    /**
     * Get total cost incurred (in USD).
     */
    public function getTotalCost(): float;

    /**
     * Check if this agent can handle the given task type.
     */
    public function canHandle(string $taskType): bool;
}
