<?php

namespace App\Console\Commands;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\Providers\GroqProvider;
use Illuminate\Console\Command;

class ContentGenerateCommand extends Command
{
    protected $signature = 'content:generate 
                            {type=post : Content type (post, campaign, email, analytics)}
                            {--prompt= : Custom prompt}
                            {--platform=twitter : Target platform (twitter, facebook, linkedin, instagram)}
                            {--model= : AI model to use}';

    protected $description = 'Generate marketing content using AI agents';

    public function handle(): int
    {
        $type = $this->argument('type');
        $platform = $this->option('platform');
        $prompt = $this->option('prompt') ?? $this->getDefaultPrompt($type, $platform);
        $model = $this->option('model') ?? config('platform.ai.providers.groq.model', 'openai/gpt-oss-20b');

        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — AI Content Generation');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $this->info("Type: {$type}");
        $this->info("Platform: {$platform}");
        $this->info("Model: {$model}");
        $this->newLine();

        $this->line("Prompt: {$prompt}");
        $this->newLine();

        try {
            $provider = new GroqProvider;

            if (! $provider->isAvailable()) {
                $this->error('Groq API key is not configured!');

                return self::FAILURE;
            }

            $request = new AiRequest(
                prompt: $prompt,
                systemPrompt: $this->getSystemPrompt($type, $platform),
                model: $model,
                task: 'creative',
            );

            $this->info('Calling AI API...');
            $startTime = microtime(true);

            $response = $provider->send($request);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);

            $this->newLine();
            $this->info('✅ Content Generated Successfully!');
            $this->line('─────────────────────────────────────');
            $this->line($response->content);
            $this->line('─────────────────────────────────────');
            $this->line("Tokens: {$response->totalTokens}");
            $this->line("Time: {$elapsed}ms");
            $this->line("Cost: \${$response->costUsd}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Content Generation Failed!');
            $this->line('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function getDefaultPrompt(string $type, string $platform): string
    {
        return match ($type) {
            'post' => "Write an engaging {$platform} post about the future of AI in digital marketing. Keep it under 280 characters for Twitter or 2000 for LinkedIn. Include relevant hashtags.",
            'campaign' => 'Create a social media campaign concept for launching a new AI-powered marketing platform. Include campaign name, target audience, key messages, and 3 sample posts.',
            'email' => 'Write a professional email announcing our new AI marketing platform to potential customers. Highlight key benefits and include a clear call to action.',
            'analytics' => 'Analyze the following social media metrics and provide actionable recommendations: Engagement rate: 4.5%, Reach: 50,000, Click-through rate: 2.1%, Conversion rate: 0.8%. What should we improve?',
            default => "Write marketing content about AI in digital marketing for {$platform}.",
        };
    }

    private function getSystemPrompt(string $type, string $platform): string
    {
        return "You are an expert digital marketing specialist. Create compelling, professional content optimized for {$platform}. Focus on engagement, clarity, and actionable insights. Use appropriate formatting and hashtags.";
    }
}
