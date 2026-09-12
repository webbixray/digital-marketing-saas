<?php

namespace App\Services\AI\Agent;

class AgentResult
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $agentName,
        public readonly bool $success,
        public readonly string $output,
        public readonly float $costUsd = 0.0,
        public readonly int $tokensUsed = 0,
        public readonly float $executionTimeMs = 0.0,
        public readonly ?string $error = null,
        public readonly array $metadata = [],
        public readonly int $timestamp = 0,
    ) {
        if ($this->timestamp === 0) {
            // Use a workaround since we can't assign in constructor body with readonly
            // We'll just leave it and handle timestamp in factory
        }
    }

    /**
     * Create a successful result.
     */
    public static function success(
        string $taskId,
        string $agentName,
        string $output,
        float $costUsd = 0.0,
        int $tokensUsed = 0,
        float $executionTimeMs = 0.0,
        array $metadata = [],
    ): self {
        return new self(
            taskId: $taskId,
            agentName: $agentName,
            success: true,
            output: $output,
            costUsd: $costUsd,
            tokensUsed: $tokensUsed,
            executionTimeMs: $executionTimeMs,
            metadata: $metadata,
            timestamp: time(),
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(
        string $taskId,
        string $agentName,
        string $error,
        float $costUsd = 0.0,
        array $metadata = [],
    ): self {
        return new self(
            taskId: $taskId,
            agentName: $agentName,
            success: false,
            output: '',
            costUsd: $costUsd,
            error: $error,
            metadata: $metadata,
            timestamp: time(),
        );
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'agent_name' => $this->agentName,
            'success' => $this->success,
            'output' => $this->output,
            'cost_usd' => $this->costUsd,
            'tokens_used' => $this->tokensUsed,
            'execution_time_ms' => $this->executionTimeMs,
            'error' => $this->error,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp,
        ];
    }
}
