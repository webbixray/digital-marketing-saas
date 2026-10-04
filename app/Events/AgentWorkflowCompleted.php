<?php

namespace App\Events;

use App\Models\AgentWorkflowExecution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentWorkflowCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentWorkflowExecution $execution,
        public readonly string $workflowName,
        public readonly int $agencyId,
        public readonly int $userId,
        public readonly bool $success,
        /**
         * @var array<int, array<string, mixed>>
         */
        public readonly array $results = [],
        public readonly ?string $errorMessage = null,
    ) {}
}
