<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NvidiaNimProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl;

    protected string $defaultModel;

    protected array $pricing = [
        '01-ai/yi-large' => ['input' => 0.00, 'output' => 0.00],
        'mistralai/mistral-large' => ['input' => 0.00, 'output' => 0.00],
        'mistralai/mistral-large-2-instruct' => ['input' => 0.00, 'output' => 0.00],
        'mistralai/mixtral-8x22b-v0.1' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-ai/deepseek-v4-flash-0731' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-ai/deepseek-v4-pro-0813' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-ai/deepseek-r1-70b' => ['input' => 0.00, 'output' => 0.00],
        'google/gemma-3-12b-it' => ['input' => 0.00, 'output' => 0.00],
        'google/gemma-3-4b-it' => ['input' => 0.00, 'output' => 0.00],
        'nvidia/llama-3.1-nemotron-70b-instruct' => ['input' => 0.00, 'output' => 0.00],
        'nvidia/llama-3.1-nemotron-51b-instruct' => ['input' => 0.00, 'output' => 0.00],
        'nvidia/mistral-nemo-12b-instruct' => ['input' => 0.00, 'output' => 0.00],
        'nvidia/nemotron-4-340b-instruct' => ['input' => 0.00, 'output' => 0.00],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.nvidia_api_key', '');
        $this->apiBaseUrl = config('platform.ai.providers.nvidia_nim.api_base_url', 'https://integrate.api.nvidia.com/v1');
        $this->defaultModel = config('platform.ai.providers.nvidia_nim.model', '01-ai/yi-large');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(120)
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
                $body = $response->body();
                $statusCode = $response->status();

                if ($statusCode === 403) {
                    throw new \RuntimeException("NVIDIA NIM authorization failed. Your API key may lack inference permissions. Visit https://build.nvidia.com to verify your key has inference access. Body: {$body}");
                }

                if ($statusCode === 410) {
                    throw new \RuntimeException("Model '{$model}' has reached end-of-life and is no longer available. Choose a different model. Body: {$body}");
                }

                if ($statusCode === 429) {
                    throw new \RuntimeException("NVIDIA NIM rate limit exceeded. Wait and try again. Body: {$body}");
                }

                throw new \RuntimeException("NVIDIA NIM API error ({$statusCode}): {$body}");
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
                    'tool_calls' => FinishReason::TOOL_CALLS->value,
                    'function_call' => FinishReason::FUNCTION_CALL->value,
                    default => FinishReason::OTHER->value,
                },
            );
        } catch (\Exception $e) {
            Log::error("NVIDIA NIM provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'nvidia_nim';
    }

    public function getDisplayName(): string
    {
        return 'NVIDIA NIM';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return [
            'mistralai/mistral-large',
            'mistralai/mistral-large-2-instruct',
            'mistralai/mixtral-8x22b-v0.1',
            'deepseek-ai/deepseek-v4-flash-0731',
            'deepseek-ai/deepseek-v4-pro-0813',
            'nvidia/llama-3.1-nemotron-70b-instruct',
            'nvidia/llama-3.1-nemotron-51b-instruct',
            'nvidia/mistral-nemo-12b-instruct',
            'nvidia/nemotron-4-340b-instruct',
            'google/gemma-3-12b-it',
            'google/gemma-3-4b-it',
            '01-ai/yi-large',
        ];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        return 0.0;
    }
}
