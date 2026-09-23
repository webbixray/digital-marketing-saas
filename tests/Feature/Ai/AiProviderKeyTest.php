<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\AiProviderKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProviderKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_ai_provider_key(): void
    {
        $agency = Agency::factory()->create();
        $key = AiProviderKey::create([
            'agency_id' => $agency->id,
            'provider_name' => 'openai',
            'api_key' => 'sk-test123',
            'priority' => 1,
        ]);

        $this->assertDatabaseHas('ai_provider_keys', [
            'agency_id' => $agency->id,
            'provider_name' => 'openai',
        ]);
    }

    public function test_api_key_attribute_is_accessible(): void
    {
        $agency = Agency::factory()->create();
        $key = AiProviderKey::create([
            'agency_id' => $agency->id,
            'provider_name' => 'openai',
            'api_key' => 'sk-test123456789',
        ]);

        $this->assertEquals('sk-test123456789', $key->api_key);
    }

    public function test_masked_key_attribute(): void
    {
        $agency = Agency::factory()->create();
        $key = AiProviderKey::create([
            'agency_id' => $agency->id,
            'provider_name' => 'openai',
            'api_key' => 'sk-1234567890abcdef',
        ]);

        $this->assertEquals('sk-1...cdef', $key->masked_key);
    }

    public function test_scope_active(): void
    {
        $agency = Agency::factory()->create();
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'openai', 'is_active' => true]);
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'anthropic', 'is_active' => false]);

        $active = AiProviderKey::byAgency($agency->id)->active()->get();
        $this->assertCount(1, $active);
    }

    public function test_scope_for_provider(): void
    {
        $agency = Agency::factory()->create();
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'openai']);
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'anthropic']);

        $openaiKeys = AiProviderKey::byAgency($agency->id)->forProvider('openai')->get();
        $this->assertCount(1, $openaiKeys);
    }

    public function test_scope_priority(): void
    {
        $agency = Agency::factory()->create();
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'groq', 'priority' => 3]);
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'openai', 'priority' => 1]);
        AiProviderKey::factory()->create(['agency_id' => $agency->id, 'provider_name' => 'anthropic', 'priority' => 2]);

        $ordered = AiProviderKey::byAgency($agency->id)->active()->priority()->get();
        $this->assertEquals('openai', $ordered[0]->provider_name);
        $this->assertEquals('anthropic', $ordered[1]->provider_name);
        $this->assertEquals('groq', $ordered[2]->provider_name);
    }

    public function test_unique_provider_per_agency(): void
    {
        $agency = Agency::factory()->create();
        AiProviderKey::create([
            'agency_id' => $agency->id,
            'provider_name' => 'openai',
            'api_key' => 'sk-first',
        ]);

        // SQLite throws a QueryException for unique constraint violations
        $this->expectException(\Illuminate\Database\QueryException::class);

        AiProviderKey::create([
            'agency_id' => $agency->id,
            'provider_name' => 'openai',
            'api_key' => 'sk-second',
        ]);
    }

    public function test_factory_creates_valid_key(): void
    {
        $key = AiProviderKey::factory()->create();
        $this->assertNotNull($key->agency_id);
        $this->assertNotNull($key->provider_name);
        $this->assertNotNull($key->api_key);
        $this->assertTrue($key->is_active);
    }

    public function test_factory_for_provider(): void
    {
        $key = AiProviderKey::factory()->forProvider('groq')->create();
        $this->assertEquals('groq', $key->provider_name);
    }
}
