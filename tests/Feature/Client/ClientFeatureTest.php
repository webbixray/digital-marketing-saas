<?php

namespace Tests\Feature\Client;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFeatureTest extends TestCase
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
        $response = $this->actingAs($this->user)->get(route('clients.index'));
        $response->assertStatus(200);
    }

    public function test_it_creates_client(): void
    {
        $response = $this->actingAs($this->user)->post(route('clients.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'company' => 'Test Company',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['name' => 'Test Client']);
    }

    public function test_it_validates_client_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('clients.store'), []);
        $response->assertSessionHasErrors();
    }

    public function test_it_shows_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('clients.show', $client));
        $response->assertStatus(200);
    }

    public function test_it_updates_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('clients.update', $client), [
            'name' => 'Updated Client',
        ]);
        $response->assertRedirect();
    }

    public function test_it_deletes_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('clients.destroy', $client));
        $response->assertRedirect();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_it_prevents_unauthorized_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('clients.show', $client));
        $response->assertForbidden();
    }

    public function test_it_searches_clients(): void
    {
        Client::factory()->create(['agency_id' => $this->agency->id, 'name' => 'ABC Company']);
        Client::factory()->create(['agency_id' => $this->agency->id, 'name' => 'XYZ Corp']);
        $response = $this->actingAs($this->user)->get(route('clients.index', ['search' => 'ABC']));
        $response->assertStatus(200);
    }
}
