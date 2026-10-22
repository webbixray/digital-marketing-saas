<?php

namespace App\Services\AI\Gateway;

class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly string $provider,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly ?float $costUsd = null,
        public readonly ?string $finishReason = null,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'provider' => $this->provider,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'cost_usd' => $this->costUsd,
            'finish_reason' => $this->finishReason,
            'metadata' => $this->metadata,
        ];
    }
}
