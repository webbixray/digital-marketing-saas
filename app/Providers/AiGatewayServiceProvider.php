<?php

namespace App\Providers;

use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\Providers\AnthropicProvider;
use App\Services\AI\Gateway\Providers\GoogleProvider;
use App\Services\AI\Gateway\Providers\GroqProvider;
use App\Services\AI\Gateway\Providers\MistralProvider;
use App\Services\AI\Gateway\Providers\NousPortalProvider;
use App\Services\AI\Gateway\Providers\NvidiaNimProvider;
use App\Services\AI\Gateway\Providers\OllamaProvider;
use App\Services\AI\Gateway\Providers\OpenAiProvider;
use App\Services\AI\Gateway\Providers\OpenRouterProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AiGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiGateway::class, function ($app) {
            $gateway = new AiGateway;

            // Register all AI providers (skip those that fail to initialize)
            $providers = [
                'openai' => OpenAiProvider::class,
                'anthropic' => AnthropicProvider::class,
                'google' => GoogleProvider::class,
                'nvidia_nim' => NvidiaNimProvider::class,
                'nous_portal' => NousPortalProvider::class,
                'ollama' => OllamaProvider::class,
                'groq' => GroqProvider::class,
                'mistral' => MistralProvider::class,
                'openrouter' => OpenRouterProvider::class,
                'openai_image' => OpenAiImageProvider::class,
                'openai_video' => OpenAiVideoProvider::class,
            ];

            foreach ($providers as $name => $class) {
                try {
                    $provider = new $class;
                    if ($provider->isAvailable()) {
                        $gateway->registerProvider($name, $provider);
                        Log::debug("AI provider registered: {$name}");
                    }
                } catch (\Throwable $e) {
                    // Skip providers that fail to initialize (e.g., missing API key)
                    Log::debug("AI provider skipped ({$name}): {$e->getMessage()}");
                }
            }

            return $gateway;
        });
    }

    public function boot(): void
    {
        //
    }
}
