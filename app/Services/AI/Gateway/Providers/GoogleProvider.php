<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected string $defaultModel;

    protected array $pricing = [
        'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
        'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
        'gemini-1.0-pro' => ['input' => 0.50, 'output' => 1.50],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.providers.google.api_key');
        $this->defaultModel = config('platform.ai.providers.google.model', 'gemini-1.5-pro');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        try {
            $prompt = $request->prompt;
            if ($request->systemPrompt) {
                $prompt = $request->systemPrompt."\n\n".$prompt;
            }

            $response = Http::timeout(60)
                ->post("{$this->apiBaseUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => $request->temperature,
                        'maxOutputTokens' => $request->maxTokens,
                    ],
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("Google API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $data['usageMetadata'] ?? [];

            return new AiResponse(
                content: $content,
                model: $model,
                provider: $this->getName(),
                promptTokens: $usage['promptTokenCount'] ?? 0,
                completionTokens: $usage['candidatesTokenCount'] ?? 0,
                totalTokens: $usage['totalTokenCount'] ?? 0,
                finishReason: match ($data['candidates'][0]['finishReason'] ?? '') {
                    'STOP' => FinishReason::STOP->value,
                    'MAX_TOKENS' => FinishReason::LENGTH->value,
                    'SAFETY' => FinishReason::CONTENT_FILTER->value,
                    default => FinishReason::OTHER->value,
                },
            );
        } catch (\Exception $e) {
            Log::error("Google provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'google';
    }

    public function getDisplayName(): string
    {
        return 'Google Gemini';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-1.0-pro'];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['gemini-1.5-pro'];
        $inputCost = ($response->promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($response->completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}
