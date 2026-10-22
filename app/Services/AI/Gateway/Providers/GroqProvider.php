<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl;

    protected string $defaultModel;

    protected array $pricing = [
        'openai/gpt-oss-120b' => ['input' => 0.000000075, 'output' => 0.0000003],
        'openai/gpt-oss-20b' => ['input' => 0.000000075, 'output' => 0.0000003],
        'qwen/qwen3.8-27b' => ['input' => 0.000000075, 'output' => 0.0000003],
        'qwen/qwen3.6-27b' => ['input' => 0.000000075, 'output' => 0.0000003],
        'groq/compound-mini' => ['input' => 0.000000075, 'output' => 0.0000003],
        'groq/compound' => ['input' => 0.000000075, 'output' => 0.0000003],
        'allam-2-7b' => ['input' => 0.000000075, 'output' => 0.0000003],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.providers.groq.api_key');
        $this->apiBaseUrl = config('platform.ai.providers.groq.api_base_url', 'https://api.groq.com/openai/v1');
        $this->defaultModel = config('platform.ai.providers.groq.model', 'openai/gpt-oss-20b');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->withoutVerifying()
                ->post("{$this->apiBaseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        ...($request->systemPrompt ? [['role' => 'system', 'content' => $request->systemPrompt]] : []),
                        ['role' => 'user', 'content' => $request->prompt],
                    ],
                    'temperature' => $request->temperature,
                    'max_tokens' => $request->maxTokens,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("Groq API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            return new AiResponse(
                content: $data['choices'][0]['message']['content'] ?? '',
                model: $data['model'] ?? $model,
                provider: $this->getName(),
                promptTokens: $data['usage']['prompt_tokens'] ?? 0,
                completionTokens: $data['usage']['completion_tokens'] ?? 0,
                totalTokens: $data['usage']['total_tokens'] ?? 0,
                costUsd: 0.0,
                finishReason: match ($data['choices'][0]['finish_reason'] ?? '') {
                    'stop' => FinishReason::STOP->value,
                    'length' => FinishReason::LENGTH->value,
                    'content_filter' => FinishReason::CONTENT_FILTER->value,
                    default => FinishReason::OTHER->value,
                },
            );
        } catch (\Exception $e) {
            Log::error("Groq provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'groq';
    }

    public function getDisplayName(): string
    {
        return 'Groq';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return [
            'openai/gpt-oss-20b',
            'openai/gpt-oss-120b',
            'qwen/qwen3.8-27b',
            'qwen/qwen3.6-27b',
            'groq/compound-mini',
            'groq/compound',
            'allam-2-7b',
        ];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['openai/gpt-oss-20b'];
        $inputCost = ($response->promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($response->completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
