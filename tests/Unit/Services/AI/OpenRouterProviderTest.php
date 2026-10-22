<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\OpenRouterProvider;
use Tests\TestCase;

class OpenRouterProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ai.providers.openrouter.api_key' => 'sk-or-test-key']);
    }

    public function test_openrouter_provider_is_available_with_key(): void
    {
        config(['platform.ai.providers.openrouter.api_key' => 'sk-or-test-key']);

        $provider = new OpenRouterProvider;

        $this->assertTrue($provider->isAvailable());
        $this->assertEquals('openrouter', $provider->getName());
        $this->assertEquals('OpenRouter', $provider->getDisplayName());
    }

    public function test_openrouter_provider_is_not_available_without_key(): void
    {
        config(['platform.ai.providers.openrouter.api_key' => '']);

        $provider = new OpenRouterProvider;

        $this->assertFalse($provider->isAvailable());
    }

    public function test_openrouter_provider_calculates_cost_correctly(): void
    {
        config(['platform.ai.providers.openrouter.api_key' => 'sk-or-test-key']);
        $provider = new OpenRouterProvider;

        $response = new AiResponse(
            content: 'Test content',
            model: 'openai/gpt-4o',
            provider: 'openrouter',
            promptTokens: 1000,
            completionTokens: 500,
            totalTokens: 1500,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // openai/gpt-4o: $2.50/1M input, $10.00/1M output
        // (1000/1000000 * 2.50) + (500/1000000 * 10.00) = 0.0025 + 0.005 = 0.0075
        $this->assertEquals(0.0075, $cost);
    }
}
