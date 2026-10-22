<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\GroqProvider;
use Tests\TestCase;

class GroqProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ai.providers.groq.api_key' => 'gsk-test-key']);
    }

    public function test_groq_provider_returns_correct_name(): void
    {
        $provider = new GroqProvider;

        $this->assertEquals('groq', $provider->getName());
        $this->assertEquals('Groq', $provider->getDisplayName());
    }

    public function test_groq_provider_is_available_with_key(): void
    {
        config(['platform.ai.providers.groq.api_key' => 'gsk-test-key']);

        $provider = new GroqProvider;

        $this->assertTrue($provider->isAvailable());
    }

    public function test_groq_provider_is_not_available_without_key(): void
    {
        config(['platform.ai.providers.groq.api_key' => '']);

        $provider = new GroqProvider;

        $this->assertFalse($provider->isAvailable());
    }

    public function test_groq_provider_returns_supported_models(): void
    {
        config(['platform.ai.providers.groq.api_key' => 'gsk-test-key']);
        $provider = new GroqProvider;

        $models = $provider->getSupportedModels();

        $this->assertContains('openai/gpt-oss-20b', $models);
        $this->assertContains('openai/gpt-oss-120b', $models);
        $this->assertContains('qwen/qwen3.8-27b', $models);
        $this->assertContains('groq/compound-mini', $models);
        $this->assertCount(7, $models);
    }

    public function test_groq_provider_calculates_zero_cost(): void
    {
        config(['platform.ai.providers.groq.api_key' => 'gsk-test-key']);
        $provider = new GroqProvider;

        // Groq models on free tier have $0 cost
        $response = new AiResponse(
            content: 'Test content',
            model: 'openai/gpt-oss-20b',
            provider: 'groq',
            promptTokens: 1000,
            completionTokens: 500,
            totalTokens: 1500,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // Groq free tier is $0
        $this->assertEquals(0.0, $cost);
    }
}
