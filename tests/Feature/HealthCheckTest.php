<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected $agency;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->user->assignRole('owner');
    }

    public function test_health_endpoint_returns_200(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/health');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_readiness_endpoint_returns_200(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/ready');
        $response->assertStatus(200);
    }

    public function test_liveness_endpoint_returns_200(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/live');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'alive']);
    }

    public function test_status_endpoint_returns_200(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/status');
        $response->assertStatus(200);
    }

    public function test_disk_space_endpoint_requires_authentication(): void
    {
        // Operational detail endpoints must not be publicly reachable.
        $this->get('/disk-space')->assertRedirect('/login');
    }

    public function test_queue_status_endpoint_requires_authentication(): void
    {
        $this->get('/queue-status')->assertRedirect('/login');
    }

    public function test_owner_can_access_disk_space_endpoint(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);
        $user->assignRole('owner');

        $this->actingAs($user)->get('/disk-space')->assertStatus(200);
    }

    public function test_public_health_endpoint_hides_infrastructure_details(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
        // Anonymous callers must not receive dependency internals.
        $response->assertJsonMissingPath('checks');
        $response->assertJsonMissingPath('environment');
    }
}
