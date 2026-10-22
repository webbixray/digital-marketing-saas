<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl;

    protected string $defaultModel;

    protected array $pricing = [
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.api_key', '');
        $this->apiBaseUrl = config('platform.ai.api_base_url', 'https://api.openai.com/v1');
        $this->defaultModel = config('platform.ai.providers.openai.model', 'gpt-4o');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
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
                throw new \RuntimeException("OpenAI API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            return new AiResponse(
                content: $data['choices'][0]['message']['content'] ?? '',
                model: $data['model'] ?? $model,
                provider: $this->getName(),
                promptTokens: $data['usage']['prompt_tokens'] ?? 0,
                completionTokens: $data['usage']['completion_tokens'] ?? 0,
                totalTokens: $data['usage']['total_tokens'] ?? 0,
                finishReason: match ($data['choices'][0]['finish_reason'] ?? '') {
                    'stop' => FinishReason::STOP->value,
                    'length' => FinishReason::LENGTH->value,
                    'content_filter' => FinishReason::CONTENT_FILTER->value,
                    default => FinishReason::OTHER->value,
                },
            );
        } catch (\Exception $e) {
            Log::error("OpenAI provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function getDisplayName(): string
    {
        return 'OpenAI';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo'];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['gpt-4o'];
        $inputCost = ($response->promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($response->completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
