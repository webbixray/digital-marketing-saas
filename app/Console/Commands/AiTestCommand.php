<?php

namespace App\Console\Commands;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\Providers\AnthropicProvider;
use App\Services\AI\Gateway\Providers\GoogleProvider;
use App\Services\AI\Gateway\Providers\GroqProvider;
use App\Services\AI\Gateway\Providers\MistralProvider;
use App\Services\AI\Gateway\Providers\NousPortalProvider;
use App\Services\AI\Gateway\Providers\NvidiaNimProvider;
use App\Services\AI\Gateway\Providers\OllamaProvider;
use App\Services\AI\Gateway\Providers\OpenAiProvider;
use App\Services\AI\Gateway\Providers\OpenRouterProvider;
use Illuminate\Console\Command;

class AiTestCommand extends Command
{
    protected $signature = 'ai:test {provider=default : Provider to test (openai, anthropic, google, nvidia_nim, nous_portal, ollama, groq, openrouter, mistral)} {--prompt= : Custom prompt to test}';

    protected $description = 'Test AI provider connection and generate content';

    public function handle(): int
    {
        $providerName = $this->argument('provider');

        if ($providerName === 'default') {
            $providerName = config('platform.ai.default_provider', 'openai');
        }

        $this->info("Testing AI Provider: {$providerName}");
        $this->newLine();

        // Create the provider directly (bypass gateway routing)
        $provider = $this->createProvider($providerName);

        if (! $provider) {
            $this->error("Provider [{$providerName}] not found or could not be created!");

            return self::FAILURE;
        }

        if (! $provider->isAvailable()) {
            $this->error("Provider [{$providerName}] is not available!");
            $this->line('Add the required API key to your .env file.');

            return self::FAILURE;
        }

        $this->info("Provider: {$provider->getDisplayName()}");
        $this->info('Supported models: '.implode(', ', $provider->getSupportedModels()));
        $this->newLine();

        // Create test prompt
        $prompt = $this->option('prompt') ?? 'Write a short, engaging tweet about the future of AI in digital marketing. Keep it under 280 characters.';
        $model = $provider->getDefaultModel();

        $this->line("Model: {$model}");
        $this->line("Prompt: {$prompt}");
        $this->newLine();

        try {
            $request = new AiRequest(
                prompt: $prompt,
                systemPrompt: 'You are a social media marketing expert. Create engaging, professional content.',
                model: $model,
            );

            $this->info('Calling AI API...');
            $startTime = microtime(true);

            $response = $provider->send($request);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);

            $this->newLine();
            $this->info('✅ API Call Successful!');
            $this->line('─────────────────────────────────────');
            $this->line("Response: {$response->content}");
            $this->line('─────────────────────────────────────');
            $this->line("Provider: {$response->provider}");
            $this->line("Model: {$response->model}");
            $this->line("Tokens: {$response->totalTokens} (prompt: {$response->promptTokens}, completion: {$response->completionTokens})");
            $this->line("Cost: \${$response->costUsd}");
            $this->line("Time: {$elapsed}ms");
            $this->line("Finish reason: {$response->finishReason}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ API Call Failed!');
            $this->line('Error: '.$e->getMessage());
            $this->newLine();

            if (str_contains($e->getMessage(), '401')) {
                $this->line('💡 Your API key may be invalid. Check your provider dashboard.');
            } elseif (str_contains($e->getMessage(), '403')) {
                $this->line('💡 Authorization failed. Your API key may lack inference permissions.');
                if ($providerName === 'nvidia_nim') {
                    $this->line('   Visit https://build.nvidia.com to verify your key has inference access.');
                    $this->line('   Some keys only allow model listing, not inference.');
                } else {
                    $this->line('   Visit your provider dashboard to check key scopes.');
                }
            } elseif (str_contains($e->getMessage(), '429')) {
                $this->line('💡 Rate limit exceeded. Wait a moment and try again.');
            } elseif (str_contains($e->getMessage(), '410')) {
                $this->line('💡 Model is no longer available (end-of-life). Choose a different model.');
            } elseif (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'timeout')) {
                $this->line('💡 Connection failed. Check:');
                $this->line('   - Your internet connection');
                $this->line('   - The API base URL in config');
                if ($providerName === 'ollama') {
                    $this->line('   - Ollama is running: ollama serve');
                    $this->line('   - Model is pulled: ollama pull llama3.1:8b');
                }
            }

            return self::FAILURE;
        }
    }

    private function createProvider(string $providerName)
    {
        return match ($providerName) {
            'openai' => new OpenAiProvider,
            'anthropic' => new AnthropicProvider,
            'google' => new GoogleProvider,
            'nvidia_nim' => new NvidiaNimProvider,
            'nous_portal' => new NousPortalProvider,
            'ollama' => new OllamaProvider,
            'groq' => new GroqProvider,
            'mistral' => new MistralProvider,
            'openrouter' => new OpenRouterProvider,
            default => null,
        };
    }
}
