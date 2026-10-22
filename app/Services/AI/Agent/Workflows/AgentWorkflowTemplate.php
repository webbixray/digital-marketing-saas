<?php

namespace App\Services\AI\Agent\Workflows;

use App\Models\Agency;

interface AgentWorkflowTemplate
{
    /**
     * Get the unique name of this workflow.
     */
    public function getName(): string;

    /**
     * Get a human-readable description of what this workflow does.
     */
    public function getDescription(): string;

    /**
     * Get the ordered list of tasks for this workflow.
     *
     * @return array<array{
     *     agent: string,
     *     task_type: string,
     *     prompt: string,
     *     data?: array,
     *     metadata?: array,
     * }>
     */
    public function getTasks(): array;

    /**
     * Get the feature codes required for this workflow to be available.
     *
     * @return array<string>
     */
    public function getRequiredFeatures(): array;

    /**
     * Check whether this workflow is available for the given agency.
     */
    public function isAvailable(Agency $agency): bool;
}
