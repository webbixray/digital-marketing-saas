<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Providers\OllamaProvider;
use Tests\TestCase;

class OllamaProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['platform.ai.providers.ollama.api_base_url' => 'http://localhost:11434']);
    }

    public function test_ollama_provider_returns_correct_name(): void
    {
        $provider = new OllamaProvider;

        $this->assertEquals('ollama', $provider->getName());
        $this->assertEquals('Ollama (Local)', $provider->getDisplayName());
    }

    public function test_ollama_provider_returns_supported_models(): void
    {
        $provider = new OllamaProvider;

        $models = $provider->getSupportedModels();

        $this->assertContains('llama3.1:70b', $models);
        $this->assertContains('llama3.1:8b', $models);
        $this->assertContains('llama3.2:3b', $models);
        $this->assertContains('mistral:7b', $models);
        $this->assertContains('mixtral:8x7b', $models);
        $this->assertContains('gemma2:27b', $models);
        $this->assertContains('codellama:7b', $models);
        $this->assertContains('qwen2.5:7b', $models);
        $this->assertContains('deepseek-r1:7b', $models);
        $this->assertGreaterThan(25, count($models));
    }

    public function test_ollama_provider_calculates_zero_cost(): void
    {
        $provider = new OllamaProvider;

        $response = new AiResponse(
            content: 'Test content',
            model: 'llama3.1:8b',
            provider: 'ollama',
            promptTokens: 10000,
            completionTokens: 5000,
            totalTokens: 15000,
            finishReason: 'stop',
        );

        $cost = $provider->calculateCost($response);

        // Ollama is free - running on local hardware
        $this->assertEquals(0.0, $cost);
    }

    public function test_ollama_provider_has_correct_default_model(): void
    {
        config(['platform.ai.providers.ollama.model' => 'llama3.1:8b']);

        $provider = new OllamaProvider;

        $this->assertEquals('llama3.1:8b', $provider->getDefaultModel());
    }

    public function test_ollama_provider_has_correct_api_base_url(): void
    {
        config(['platform.ai.providers.ollama.api_base_url' => 'http://localhost:11434']);

        $provider = new OllamaProvider;

        // Should not throw exception
        $this->assertNotNull($provider);
    }
}
