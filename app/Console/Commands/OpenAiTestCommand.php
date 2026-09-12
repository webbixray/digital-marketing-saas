<?php

namespace App\Console\Commands;

use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class OpenAiTestCommand extends Command
{
    protected $signature = 'openai:test {--prompt= : Custom prompt to test}';

    protected $description = 'Test OpenAI API connection and generate content';

    public function handle(): int
    {
        $this->info('Testing OpenAI API Connection...');
        $this->newLine();

        // Check if API key is configured
        $apiKey = config('platform.ai.api_key');
        if (empty($apiKey)) {
            $this->error('AI_API_KEY is not configured!');
            $this->line('');
            $this->line('To configure:');
            $this->line('1. Get an API key from https://platform.openai.com/api-keys');
            $this->line('2. Add it to your .env file:');
            $this->line('   AI_API_KEY=sk-your-key-here');
            $this->line('3. Run: php artisan config:clear');

            return self::FAILURE;
        }

        // Mask key for display
        $maskedKey = substr($apiKey, 0, 8).'...'.substr($apiKey, -4);
        $this->info("API Key: {$maskedKey}");
        $this->info('Provider: '.config('platform.ai.default_provider', 'openai'));
        $this->info('Model: '.config('platform.ai.providers.openai.model', 'gpt-4o'));
        $this->newLine();

        // Create gateway and test
        try {
            $gateway = App::make(AiGateway::class);

            $prompt = $this->option('prompt') ?? 'Write a short, engaging tweet about the future of AI in marketing. Keep it under 280 characters.';

            $this->line("Prompt: {$prompt}");
            $this->newLine();

            $request = AiRequest::creative(
                prompt: $prompt,
                systemPrompt: 'You are a social media marketing expert. Create engaging, professional content.',
                model: config('platform.ai.providers.openai.model', 'gpt-4o'),
            );

            $this->info('Calling OpenAI API...');
            $startTime = microtime(true);

            $response = $gateway->send($request);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);

            $this->newLine();
            $this->info('✅ API Call Successful!');
            $this->line('─────────────────────────────────────');
            $this->line("Response: {$response->content}");
            $this->line('─────────────────────────────────────');
            $this->line("Model: {$response->model}");
            $this->line("Provider: {$response->provider}");
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
                $this->line('💡 Your API key may be invalid. Check at:');
                $this->line('   https://platform.openai.com/api-keys');
            } elseif (str_contains($e->getMessage(), '429')) {
                $this->line('💡 Rate limit exceeded. Wait a moment and try again.');
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $this->line('💡 Request timed out. Check your internet connection.');
            }

            return self::FAILURE;
        }
    }
}
