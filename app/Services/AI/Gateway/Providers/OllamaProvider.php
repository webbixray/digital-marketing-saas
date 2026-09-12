<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider implements AiProviderInterface
{
    protected string $apiBaseUrl;

    protected string $defaultModel;

    protected array $pricing = [
        'llama3.1:70b' => ['input' => 0.00, 'output' => 0.00],
        'llama3.1:8b' => ['input' => 0.00, 'output' => 0.00],
        'llama3.2:3b' => ['input' => 0.00, 'output' => 0.00],
        'llama3.2:1b' => ['input' => 0.00, 'output' => 0.00],
        'mistral:7b' => ['input' => 0.00, 'output' => 0.00],
        'mixtral:8x7b' => ['input' => 0.00, 'output' => 0.00],
        'gemma2:27b' => ['input' => 0.00, 'output' => 0.00],
        'gemma2:9b' => ['input' => 0.00, 'output' => 0.00],
        'gemma2:2b' => ['input' => 0.00, 'output' => 0.00],
        'codellama:70b' => ['input' => 0.00, 'output' => 0.00],
        'codellama:34b' => ['input' => 0.00, 'output' => 0.00],
        'codellama:13b' => ['input' => 0.00, 'output' => 0.00],
        'codellama:7b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:72b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:32b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:14b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:7b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:3b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:1.5b' => ['input' => 0.00, 'output' => 0.00],
        'qwen2.5:0.5b' => ['input' => 0.00, 'output' => 0.00],
        'phi3.5:3.8b' => ['input' => 0.00, 'output' => 0.00],
        'phi3:14b' => ['input' => 0.00, 'output' => 0.00],
        'phi3:3.8b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-coder:6.7b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-coder:33b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-r1:70b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-r1:32b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-r1:14b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-r1:7b' => ['input' => 0.00, 'output' => 0.00],
        'deepseek-r1:1.5b' => ['input' => 0.00, 'output' => 0.00],
        'nemotron-mini:4b' => ['input' => 0.00, 'output' => 0.00],
        'llava:34b' => ['input' => 0.00, 'output' => 0.00],
        'llava:13b' => ['input' => 0.00, 'output' => 0.00],
        'llava:7b' => ['input' => 0.00, 'output' => 0.00],
    ];

    public function __construct()
    {
        $this->apiBaseUrl = config('platform.ai.providers.ollama.api_base_url', 'http://localhost:11434');
        $this->defaultModel = config('platform.ai.providers.ollama.model', 'llama3.1:8b');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        try {
            $response = Http::timeout(120)
                ->post("{$this->apiBaseUrl}/api/chat", [
                    'model' => $model,
                    'messages' => [
                        ...($request->systemPrompt ? [['role' => 'system', 'content' => $request->systemPrompt]] : []),
                        ['role' => 'user', 'content' => $request->prompt],
                    ],
                    'stream' => false,
                    'options' => [
                        'temperature' => $request->temperature ?? 0.7,
                        'num_predict' => $request->maxTokens ?? 2048,
                    ],
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("Ollama API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            $promptTokens = $data['prompt_eval_count'] ?? 0;
            $completionTokens = $data['eval_count'] ?? 0;

            return new AiResponse(
                content: $data['message']['content'] ?? '',
                model: $data['model'] ?? $model,
                provider: $this->getName(),
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $promptTokens + $completionTokens,
                costUsd: 0.0,
                finishReason: $data['done'] ? FinishReason::STOP->value : FinishReason::LENGTH->value,
            );
        } catch (\Exception $e) {
            Log::error("Ollama provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'ollama';
    }

    public function getDisplayName(): string
    {
        return 'Ollama (Local)';
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->apiBaseUrl}/api/tags");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSupportedModels(): array
    {
        return [
            // Llama family
            'llama3.1:70b',
            'llama3.1:8b',
            'llama3.2:3b',
            'llama3.2:1b',
            // Mistral family
            'mistral:7b',
            'mixtral:8x7b',
            // Gemma family
            'gemma2:27b',
            'gemma2:9b',
            'gemma2:2b',
            // Code models
            'codellama:70b',
            'codellama:34b',
            'codellama:13b',
            'codellama:7b',
            // Qwen family
            'qwen2.5:72b',
            'qwen2.5:32b',
            'qwen2.5:14b',
            'qwen2.5:7b',
            'qwen2.5:3b',
            'qwen2.5:1.5b',
            'qwen2.5:0.5b',
            // Phi family
            'phi3.5:3.8b',
            'phi3:14b',
            'phi3:3.8b',
            // DeepSeek family
            'deepseek-coder:6.7b',
            'deepseek-coder:33b',
            'deepseek-r1:70b',
            'deepseek-r1:32b',
            'deepseek-r1:14b',
            'deepseek-r1:7b',
            'deepseek-r1:1.5b',
            // Other
            'nemotron-mini:4b',
            'llava:34b',
            'llava:13b',
            'llava:7b',
        ];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        // Ollama is free - running on local hardware
        return 0.0;
    }

    /**
     * Get list of models currently available on the local Ollama instance.
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->apiBaseUrl}/api/tags");

            if ($response->successful()) {
                $data = $response->json();

                return array_map(fn ($model) => $model['name'], $data['models'] ?? []);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch Ollama models: {$e->getMessage()}");
        }

        return [];
    }
}
