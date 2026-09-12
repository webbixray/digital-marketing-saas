<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\MistralProvider;
use Tests\TestCase;

class MistralProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ai.api_key' => 'test-mistral-key']);
    }

    public function test_mistral_provider_is_available_with_key(): void
    {
        config(['platform.ai.api_key' => 'test-mistral-key']);

        $provider = new MistralProvider;

        $this->assertTrue($provider->isAvailable());
        $this->assertEquals('mistral', $provider->getName());
        $this->assertEquals('Mistral AI', $provider->getDisplayName());
    }

    public function test_mistral_provider_returns_supported_models(): void
    {
        config(['platform.ai.api_key' => 'test-mistral-key']);
        $provider = new MistralProvider;

        $models = $provider->getSupportedModels();

        $this->assertContains('mistral-large', $models);
        $this->assertContains('mistral-small', $models);
        $this->assertContains('mistral-medium', $models);
        $this->assertContains('mixtral-8x7b', $models);
        $this->assertContains('mixtral-8x22b', $models);
    }

    public function test_mistral_provider_calculates_cost_correctly(): void
    {
        config(['platform.ai.api_key' => 'test-mistral-key']);
        $provider = new MistralProvider;

        $response = new AiResponse(
            content: 'Test content',
            model: 'mistral-large',
            provider: 'mistral',
            promptTokens: 1000,
            completionTokens: 500,
            totalTokens: 1500,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // mistral-large: $3.00/1M input, $9.00/1M output
        // (1000/1000000 * 3.00) + (500/1000000 * 9.00) = 0.003 + 0.0045 = 0.0075
        $this->assertEquals(0.0075, $cost);
    }
}
