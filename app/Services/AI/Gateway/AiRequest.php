<?php

namespace App\Services\AI\Gateway;

class AiRequest
{
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $systemPrompt = null,
        public readonly string $model = 'gpt-4o',
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 2048,
        public readonly string $task = 'fast',
        public readonly ?string $contentType = null,
        public readonly ?string $action = null,
        public readonly array $messages = [],
        public readonly ?string $provider = null,
    ) {}

    public function withProvider(string $provider): self
    {
        return new self(
            prompt: $this->prompt,
            systemPrompt: $this->systemPrompt,
            model: $this->model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            task: $this->task,
            contentType: $this->contentType,
            action: $this->action,
            messages: $this->messages,
            provider: $provider,
        );
    }

    public function withModel(string $model): self
    {
        return new self(
            prompt: $this->prompt,
            systemPrompt: $this->systemPrompt,
            model: $model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            task: $this->task,
            contentType: $this->contentType,
            action: $this->action,
            messages: $this->messages,
            provider: $this->provider,
        );
    }

    public function withPrompt(string $prompt): self
    {
        return new self(
            prompt: $prompt,
            systemPrompt: $this->systemPrompt,
            model: $this->model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            task: $this->task,
            contentType: $this->contentType,
            action: $this->action,
            messages: $this->messages,
            provider: $this->provider,
        );
    }

    public function withSystemPrompt(?string $systemPrompt): self
    {
        return new self(
            prompt: $this->prompt,
            systemPrompt: $systemPrompt,
            model: $this->model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            task: $this->task,
            contentType: $this->contentType,
            action: $this->action,
            messages: $this->messages,
            provider: $this->provider,
        );
    }

    public static function text(string $prompt, ?string $systemPrompt = null, string $model = 'gpt-4o', string $task = 'fast'): self
    {
        return new self(
            prompt: $prompt,
            systemPrompt: $systemPrompt,
            model: $model,
            task: $task,
        );
    }

    public static function creative(string $prompt, ?string $systemPrompt = null, string $model = 'gpt-4o'): self
    {
        return new self(
            prompt: $prompt,
            systemPrompt: $systemPrompt,
            model: $model,
            task: 'creative',
        );
    }

    public static function reasoning(string $prompt, ?string $systemPrompt = null, string $model = 'gpt-4o'): self
    {
        return new self(
            prompt: $prompt,
            systemPrompt: $systemPrompt,
            model: $model,
            task: 'reasoning',
        );
    }

    public static function analysis(string $prompt, ?string $systemPrompt = null, string $model = 'gpt-4o'): self
    {
        return new self(
            prompt: $prompt,
            systemPrompt: $systemPrompt,
            model: $model,
            task: 'analysis',
        );
    }

    public function toArray(): array
    {
        return [
            'prompt' => $this->prompt,
            'system_prompt' => $this->systemPrompt,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'task' => $this->task,
            'content_type' => $this->contentType,
            'action' => $this->action,
            'messages' => $this->messages,
            'provider' => $this->provider,
        ];
    }
}
