<?php

namespace Tests\Unit\Services\AI;

use App\Models\Agency;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\OpenAiProvider;
use Mockery;
use Tests\TestCase;

class OpenAiProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set default config to avoid null typed property errors
        config(['platform.ai.api_key' => 'sk-test-key']);
    }

    public function test_openai_provider_is_available_with_key(): void
    {
        config(['platform.ai.api_key' => 'sk-test-key']);

        $provider = new OpenAiProvider;

        $this->assertTrue($provider->isAvailable());
        $this->assertEquals('openai', $provider->getName());
        $this->assertEquals('OpenAI', $provider->getDisplayName());
    }

    public function test_openai_provider_is_not_available_without_key(): void
    {
        config(['platform.ai.api_key' => '']);

        $provider = new OpenAiProvider;

        $this->assertFalse($provider->isAvailable());
    }

    public function test_openai_provider_returns_supported_models(): void
    {
        config(['platform.ai.api_key' => 'sk-test-key']);
        $provider = new OpenAiProvider;

        $models = $provider->getSupportedModels();

        $this->assertContains('gpt-4o', $models);
        $this->assertContains('gpt-4o-mini', $models);
        $this->assertContains('gpt-3.5-turbo', $models);
    }

    public function test_openai_provider_calculates_cost_correctly(): void
    {
        config(['platform.ai.api_key' => 'sk-test-key']);
        $provider = new OpenAiProvider;

        $response = new AiResponse(
            content: 'Test content',
            model: 'gpt-4o',
            provider: 'openai',
            promptTokens: 1000,
            completionTokens: 500,
            totalTokens: 1500,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // gpt-4o: $2.50/1M input, $10.00/1M output
        // (1000/1000000 * 2.50) + (500/1000000 * 10.00) = 0.0025 + 0.005 = 0.0075
        $this->assertEquals(0.0075, $cost);
    }

    public function test_openai_provider_calculates_cost_for_mini_model(): void
    {
        config(['platform.ai.api_key' => 'sk-test-key']);
        $provider = new OpenAiProvider;

        $response = new AiResponse(
            content: 'Test content',
            model: 'gpt-4o-mini',
            provider: 'openai',
            promptTokens: 1000,
            completionTokens: 500,
            totalTokens: 1500,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // gpt-4o-mini: $0.15/1M input, $0.60/1M output
        // (1000/1000000 * 0.15) + (500/1000000 * 0.60) = 0.00015 + 0.0003 = 0.00045
        $this->assertEquals(0.00045, $cost);
    }

    public function test_ai_gateway_routes_to_openai_provider(): void
    {
        config([
            'platform.ai.api_key' => 'sk-test-key',
            'platform.ai.default_provider' => 'openai',
            'platform.ai.routing' => [], // Disable task-based routing for this test
        ]);

        $mockProvider = Mockery::mock(OpenAiProvider::class);
        $mockProvider->shouldReceive('isAvailable')->andReturn(true);
        $mockProvider->shouldReceive('getName')->andReturn('openai');
        $mockProvider->shouldReceive('send')->once()->andReturn(
            new AiResponse(
                content: 'AI generated content',
                model: 'gpt-4o',
                provider: 'openai',
                promptTokens: 100,
                completionTokens: 50,
                totalTokens: 150,
                finishReason: 'stop',
            )
        );
        $mockProvider->shouldReceive('calculateCost')->once()->andReturn(0.001);

        $gateway = new AiGateway;
        $gateway->registerProvider('openai', $mockProvider);

        $request = AiRequest::creative(
            prompt: 'Test prompt',
            model: 'gpt-4o',
        );

        // Create a mock agency
        $agency = new Agency;
        $agency->id = 1;
        $agency->subscription_plan = 'pro';

        $response = $gateway->send($request, $agency);

        $this->assertEquals('AI generated content', $response->content);
        $this->assertEquals(150, $response->totalTokens);
    }
}
