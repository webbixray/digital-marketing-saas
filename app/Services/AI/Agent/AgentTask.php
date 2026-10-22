<?php

namespace App\Services\AI\Agent;

class AgentTask
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $prompt,
        public readonly array $data = [],
        public readonly array $previousOutputs = [],
        public readonly ?string $preferredAgent = null,
        public readonly int $maxRetries = 3,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a task with previous step output appended.
     */
    public function withPreviousOutput(string $output): self
    {
        return new self(
            id: $this->id,
            type: $this->type,
            prompt: $this->prompt,
            data: $this->data,
            previousOutputs: [...$this->previousOutputs, $output],
            preferredAgent: $this->preferredAgent,
            maxRetries: $this->maxRetries,
            metadata: $this->metadata,
        );
    }
}
