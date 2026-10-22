<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_it_lists_clients(): void
    {
        Client::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/clients');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_it_creates_a_client(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/clients', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('clients', ['name' => 'Test Client']);
    }

    public function test_it_validates_client_creation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/clients', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_it_shows_a_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/clients/{$client->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $client->id);
    }

    public function test_it_prevents_showing_other_agency_clients(): void
    {
        $otherAgency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/clients/{$client->id}");

        $response->assertNotFound();
    }

    public function test_it_updates_a_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'Updated Client',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Updated Client']);
    }

    public function test_it_deletes_a_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/clients');

        $response->assertUnauthorized();
    }
}
