<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl = 'https://api.anthropic.com/v1';

    protected string $defaultModel;

    protected array $pricing = [
        'claude-3-5-sonnet-20241022' => ['input' => 3.00, 'output' => 15.00],
        'claude-3-haiku-20240307' => ['input' => 0.25, 'output' => 1.25],
        'claude-3-opus-20240229' => ['input' => 15.00, 'output' => 75.00],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.providers.anthropic.api_key');
        $this->defaultModel = config('platform.ai.providers.anthropic.model', 'claude-3-5-sonnet-20241022');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
                ->timeout(60)
                ->post("{$this->apiBaseUrl}/messages", [
                    'model' => $model,
                    'max_tokens' => $request->maxTokens,
                    'temperature' => $request->temperature,
                    ...($request->systemPrompt ? ['system' => $request->systemPrompt] : []),
                    'messages' => [
                        ['role' => 'user', 'content' => $request->prompt],
                    ],
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("Anthropic API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            return new AiResponse(
                content: $data['content'][0]['text'] ?? '',
                model: $data['model'] ?? $model,
                provider: $this->getName(),
                promptTokens: $data['usage']['input_tokens'] ?? 0,
                completionTokens: $data['usage']['output_tokens'] ?? 0,
                totalTokens: ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                finishReason: match ($data['stop_reason'] ?? '') {
                    'end_turn' => FinishReason::STOP->value,
                    'max_tokens' => FinishReason::LENGTH->value,
                    'stop_sequence' => FinishReason::STOP->value,
                    default => FinishReason::OTHER->value,
                },
            );
        } catch (\Exception $e) {
            Log::error("Anthropic provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    public function getDisplayName(): string
    {
        return 'Anthropic Claude';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return ['claude-3-5-sonnet-20241022', 'claude-3-haiku-20240307', 'claude-3-opus-20240229'];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['claude-3-5-sonnet-20241022'];
        $inputCost = ($response->promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($response->completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
