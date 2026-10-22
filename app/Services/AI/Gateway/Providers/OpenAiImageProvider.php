<?php

namespace App\Services\AI\Gateway\Providers;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Enums\FinishReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiImageProvider implements AiProviderInterface
{
    protected string $apiKey;

    protected string $apiBaseUrl;

    protected string $defaultModel;

    protected array $pricing = [
        'dall-e-2' => ['input' => 0.018, 'output' => 0.018], // per image? Actually pricing is per image.
        'dall-e-3' => ['input' => 0.040, 'output' => 0.040],
    ];

    public function __construct()
    {
        $this->apiKey = config('platform.ai.api_key', '');
        $this->apiBaseUrl = config('platform.ai.api_base_url', 'https://api.openai.com/v1');
        $this->defaultModel = config('platform.ai.providers.openai.model', 'dall-e-3');
    }

    public function send(AiRequest $request): AiResponse
    {
        $model = $request->model ?? $this->defaultModel;

        // Validate model
        if (! in_array($model, ['dall-e-2', 'dall-e-3'], true)) {
            $model = $this->defaultModel;
        }

        // Extract size from prompt? The service includes dimensions in prompt.
        // We'll try to parse size from prompt like "1024x1024" or use default.
        $size = $this->extractSizeFromPrompt($request->prompt);
        if (! $size) {
            $size = '1024x1024';
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->apiBaseUrl}/images/generations", [
                    'prompt' => $this->stripSizeFromPrompt($request->prompt),
                    'model' => $model,
                    'n' => 1,
                    'size' => $size,
                    'response_format' => 'url',
                ]);

            if ($response->failed()) {
                throw new \RuntimeException("OpenAI Image API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            // Assuming we requested n=1
            $imageUrl = $data['data'][0]['url'] ?? '';

            return new AiResponse(
                content: $imageUrl,
                model: $data['model'] ?? $model,
                provider: $this->getName(),
                promptTokens: 0, // Not applicable
                completionTokens: 0, // Not applicable
                totalTokens: 0, // Not applicable
                finishReason: FinishReason::STOP->value,
            );
        } catch (\Exception $e) {
            Log::error("OpenAI Image provider error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getName(): string
    {
        return 'openai_image';
    }

    public function getDisplayName(): string
    {
        return 'OpenAI Image (DALL·E)';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function getSupportedModels(): array
    {
        return ['dall-e-2', 'dall-e-3'];
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function calculateCost(AiResponse $response): float
    {
        // Pricing per image
        $model = $response->model;
        $pricing = $this->pricing[$model] ?? $this->pricing['dall-e-3'];
        // Input cost is per image, output not applicable
        $inputCost = $pricing['input']; // per image
        // For simplicity, we treat cost as per image
        return round($inputCost, 6);
    }

    /** Extract size like 1024x1024 from prompt */
    private function extractSizeFromPrompt(string $prompt): ?string
    {
        if (preg_match('/\b(\d{3,4}x\d{3,4})\b/', $prompt, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /** Remove size snippet from prompt to avoid sending it to AI */
    private function stripSizeFromPrompt(string $prompt): string
    {
        return preg_replace('/\b\d{3,4}x\d{3,4}\b/', '', $prompt);
    }
}
