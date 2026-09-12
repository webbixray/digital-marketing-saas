<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\NousPortalProvider;
use Tests\TestCase;

class NousPortalProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ai.providers.nous_portal.api_key' => 'nous-test-key']);
    }

    public function test_nous_portal_provider_is_available_with_key(): void
    {
        config(['platform.ai.providers.nous_portal.api_key' => 'nous-test-key']);

        $provider = new NousPortalProvider;

        $this->assertTrue($provider->isAvailable());
        $this->assertEquals('nous_portal', $provider->getName());
        $this->assertEquals('Nous Portal', $provider->getDisplayName());
    }

    public function test_nous_portal_provider_is_not_available_without_key(): void
    {
        config(['platform.ai.providers.nous_portal.api_key' => '']);

        $provider = new NousPortalProvider;

        $this->assertFalse($provider->isAvailable());
    }

    public function test_nous_portal_provider_returns_supported_models(): void
    {
        $provider = new NousPortalProvider;

        $models = $provider->getSupportedModels();

        $this->assertContains('hermes-3-llama-3.1-70b', $models);
        $this->assertContains('hermes-3-llama-3.1-8b', $models);
        $this->assertContains('llama-3.1-70b', $models);
        $this->assertContains('llama-3.1-8b', $models);
        $this->assertContains('mistral-7b', $models);
        $this->assertContains('mixtral-8x7b', $models);
        $this->assertCount(12, $models);
    }

    public function test_nous_portal_provider_calculates_cost_correctly(): void
    {
        $provider = new NousPortalProvider;

        // Nous Portal models are free, so cost should always be 0
        $response = new AiResponse(
            content: 'Test content',
            model: 'hermes-3-llama-3.1-70b',
            provider: 'nous_portal',
            promptTokens: 10000,
            completionTokens: 5000,
            totalTokens: 15000,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // All Nous Portal models are currently free
        $this->assertEquals(0.0, $cost);
    }

    public function test_nous_portal_provider_has_correct_api_base_url(): void
    {
        config([
            'platform.ai.providers.nous_portal.api_key' => 'test-key',
            'platform.ai.providers.nous_portal.api_base_url' => 'https://portal.nous.co/api/v1',
        ]);

        $provider = new NousPortalProvider;

        $this->assertTrue($provider->isAvailable());
    }
}
