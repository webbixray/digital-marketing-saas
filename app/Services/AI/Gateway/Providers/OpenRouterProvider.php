<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl = 'https://openrouter.ai/api/v1';

    protected string $defaultModel;

    protected array $fallbackModels;

    protected array $pricing = [
        // OpenAI models via OpenRouter
        'openai/gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'openai/gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'openai/gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
        'openai/gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
        // Anthropic models via OpenRouter
        'anthropic/claude-3.5-sonnet' => ['input' => 3.00, 'output' => 15.00],
        'anthropic/claude-3.5-haiku' => ['input' => 0.25, 'output' => 1.25],
        'anthropic/claude-3-opus' => ['input' => 15.00, 'output' => 75.00],
        // Meta Llama models via OpenRouter
        'meta-llama/llama-3.1-405b-instruct' => ['input' => 3.50, 'output' => 3.50],
        'meta-llama/llama-3.1-70b-instruct' => ['input' => 0.40, 'output' => 0.40],
        'meta-llama/llama-3.1-8b-instruct' => ['input' => 0.05, 'output' => 0.05],
        // Mistral models via OpenRouter
        'mistralai/mistral-large' => ['input' => 2.00, 'output' => 6.00],
        'mistralai/mistral-medium' => ['input' => 0.27, 'output' => 0.81],
        'mistralai/mistral-small' => ['input' => 0.20, 'output' => 0.60],
        'mistralai/mixtral-8x7b-instruct' => ['input' => 0.60, 'output' => 0.60],
        // Google models via OpenRouter
        'google/gemini-pro-1.5' => ['input' => 1.25, 'output' => 5.00],
        'google/gemini-flash-1.5' => ['input' => 0.075, 'output' => 0.30],
        // Other models
        'cohere/command-r-plus' => ['input' => 3.00, 'output' => 15.00],
        'cohere/command-r' => ['input' => 0.50, 'output' => 1.50],
        'perplexity/llama-3.1-sonar-large-128k-online' => ['input' => 1.00, 'output' => 1.00],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.providers.openrouter.api_key');
        $this->defaultModel = config('platform.ai.providers.openrouter.model', 'openai/gpt-4o');
        $this->fallbackModels = config('platform.ai.providers.openrouter.fallback_models', ['openai/gpt-4o-mini', 'meta-llama/llama-3.1-70b-instruct']);
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        // Ensure model has provider prefix for OpenRouter
        $model = $this->normalizeModelName($model);

        $models = array_unique(array_merge([$model], $this->fallbackModels));

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title' => config('app.name', 'DigitalMarketingSaaS'),
                ])
                ->timeout(120)
                ->post("{$this->apiBaseUrl}/chat/completions", [
                    'model' => $model,
                    'models' => $models,
                    'messages' => [
                        ...($request->systemPrompt ? [['role' => 'system', 'content' => $request->systemPrompt]] : []),
                        ['role' => 'user', 'content' => $request->prompt],
                    ],
                    'temperature' => $request->temperature,
                    'max_tokens' => $request->maxTokens,
                    'route' => 'fallback',
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("OpenRouter API error: {$response->status()} - {$response->body()}");
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
            Log::error("OpenRouter provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'openrouter';
    }

    public function getDisplayName(): string
    {
        return 'OpenRouter';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return [
            'openai/gpt-4o',
            'openai/gpt-4o-mini',
            'openai/gpt-4-turbo',
            'openai/gpt-3.5-turbo',
            'anthropic/claude-3.5-sonnet',
            'anthropic/claude-3.5-haiku',
            'anthropic/claude-3-opus',
            'meta-llama/llama-3.1-405b-instruct',
            'meta-llama/llama-3.1-70b-instruct',
            'meta-llama/llama-3.1-8b-instruct',
            'mistralai/mistral-large',
            'mistralai/mistral-medium',
            'mistralai/mistral-small',
            'mistralai/mixtral-8x7b-instruct',
            'google/gemini-pro-1.5',
            'google/gemini-flash-1.5',
            'cohere/command-r-plus',
            'cohere/command-r',
            'perplexity/llama-3.1-sonar-large-128k-online',
        ];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['openai/gpt-4o'];
        $inputCost = ($response->promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($response->completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }

    protected function normalizeModelName(string $model): string
    {
        // If model already has a provider prefix (contains /), use as-is
        if (str_contains($model, '/')) {
            return $model;
        }

        // Map common model names to OpenRouter format
        $mapping = [
            'gpt-4o' => 'openai/gpt-4o',
            'gpt-4o-mini' => 'openai/gpt-4o-mini',
            'gpt-4-turbo' => 'openai/gpt-4-turbo',
            'gpt-3.5-turbo' => 'openai/gpt-3.5-turbo',
            'claude-3-5-sonnet-20241022' => 'anthropic/claude-3.5-sonnet',
            'claude-3-haiku-20240307' => 'anthropic/claude-3.5-haiku',
            'claude-3-opus-20240229' => 'anthropic/claude-3-opus',
            'gemini-1.5-pro' => 'google/gemini-pro-1.5',
            'gemini-1.5-flash' => 'google/gemini-flash-1.5',
        ];

        return $mapping[$model] ?? "openai/{$model}";
    }
}
