<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl;

    protected string $defaultModel;

    protected array $pricing = [
        'mistral-large' => ['input' => 3.00, 'output' => 9.00],
        'mistral-small' => ['input' => 1.00, 'output' => 3.00],
        'mistral-medium' => ['input' => 2.75, 'output' => 8.10],
        'mixtral-8x7b' => ['input' => 0.70, 'output' => 0.70],
        'mixtral-8x22b' => ['input' => 2.00, 'output' => 6.00],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.api_key');
        $this->apiBaseUrl = config('platform.ai.providers.mistral.base_url', 'https://api.mistral.ai/v1');
        $this->defaultModel = config('platform.ai.providers.mistral.model', 'mistral-large');
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
                throw new \RuntimeException("Mistral API error: {$response->status()} - {$response->body()}");
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
            Log::error("Mistral provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'mistral';
    }

    public function getDisplayName(): string
    {
        return 'Mistral AI';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return ['mistral-large', 'mistral-small', 'mistral-medium', 'mixtral-8x7b', 'mixtral-8x22b'];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['mistral-large'];
        $inputCost = ($response->promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($response->completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
