<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\AiProviderKey;
use App\Services\AI\AiProviderManager;
use App\Services\AI\Gateway\AiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProviderManagerTest extends TestCase
{
    use RefreshDatabase;

    private AiProviderManager $manager;
    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $gateway = new AiGateway();
        $this->manager = new AiProviderManager($gateway);
        $this->agency = Agency::factory()->create();
    }

    public function test_get_available_providers_returns_all(): void
    {
        $providers = $this->manager->getAvailableProviders($this->agency->id);
        $this->assertCount(8, $providers);
    }

    public function test_available_providers_shows_byok_status(): void
    {
        $this->manager->setByokKey($this->agency->id, 'openai', 'sk-test');
        $providers = $this->manager->getAvailableProviders($this->agency->id);

        $openai = collect($providers)->firstWhere('name', 'openai');
        $this->assertTrue($openai['byok_configured']);
    }

    public function test_set_byok_key(): void
    {
        $key = $this->manager->setByokKey(
            $this->agency->id,
            'anthropic',
            'sk-ant-test',
            null,
            1,
            'My Anthropic key'
        );

        $this->assertEquals('anthropic', $key->provider_name);
        $this->assertEquals('sk-ant-test', $key->api_key);
        $this->assertEquals(1, $key->priority);
    }

    public function test_remove_byok_key(): void
    {
        $this->manager->setByokKey($this->agency->id, 'openai', 'sk-test');
        $this->manager->removeByokKey($this->agency->id, 'openai');

        $this->assertDatabaseMissing('ai_provider_keys', [
            'agency_id' => $this->agency->id,
            'provider_name' => 'openai',
        ]);
    }

    public function test_get_routing_recommendation(): void
    {
        $recommendations = $this->manager->getRoutingRecommendation('fast', $this->agency->id);
        $this->assertNotEmpty($recommendations);

        $first = $recommendations[0];
        $this->assertArrayHasKey('provider', $first);
        $this->assertArrayHasKey('model', $first);
        $this->assertArrayHasKey('cost_per_1m_input', $first);
    }

    public function test_get_active_byok_keys(): void
    {
        $this->manager->setByokKey($this->agency->id, 'openai', 'sk-test1', null, 2);
        $this->manager->setByokKey($this->agency->id, 'anthropic', 'sk-test2', null, 1);

        $keys = $this->manager->getActiveByokKeys($this->agency->id);
        $this->assertCount(2, $keys);
        $this->assertEquals('anthropic', $keys->first()->provider_name);
    }

    public function test_get_provider_models(): void
    {
        $models = $this->manager->getProviderModels('openai');
        $this->assertNotEmpty($models);
        $this->assertContains('gpt-4o', $models);

        $models = $this->manager->getProviderModels('ollama');
        $this->assertNotEmpty($models);
        $this->assertContains('llama3.1:8b', $models);
    }
}
