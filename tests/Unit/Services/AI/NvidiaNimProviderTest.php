<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\NvidiaNimProvider;
use Tests\TestCase;

class NvidiaNimProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ai.nvidia_api_key' => 'nv-test-key']);
    }

    public function test_nvidia_nim_provider_returns_correct_name_and_models(): void
    {
        $provider = new NvidiaNimProvider;

        $this->assertEquals('nvidia_nim', $provider->getName());
        $this->assertEquals('NVIDIA NIM', $provider->getDisplayName());
        $this->assertTrue($provider->isAvailable());

        $models = $provider->getSupportedModels();
        $this->assertContains('mistralai/mistral-large', $models);
        $this->assertContains('mistralai/mistral-large-2-instruct', $models);
        $this->assertContains('nvidia/llama-3.1-nemotron-70b-instruct', $models);
        $this->assertContains('nvidia/nemotron-4-340b-instruct', $models);
        $this->assertContains('google/gemma-3-12b-it', $models);
        $this->assertContains('01-ai/yi-large', $models);
    }

    public function test_nvidia_nim_provider_is_not_available_without_key(): void
    {
        config(['platform.ai.nvidia_api_key' => '']);

        $provider = new NvidiaNimProvider;

        $this->assertFalse($provider->isAvailable());
    }

    public function test_nvidia_nim_provider_calculates_cost_correctly(): void
    {
        config(['platform.ai.nvidia_api_key' => 'nv-test-key']);
        $provider = new NvidiaNimProvider;

        $response = new AiResponse(
            content: 'Test content from NVIDIA NIM',
            model: 'mistralai/mistral-large',
            provider: 'nvidia_nim',
            promptTokens: 5000,
            completionTokens: 2000,
            totalTokens: 7000,
            finishReason: 'stop',
        );

        // All NVIDIA NIM models have $0.0 pricing (free tier / self-hosted)
        $cost = $provider->calculateCost($response);
        $this->assertEquals(0.0, $cost);
    }
}
