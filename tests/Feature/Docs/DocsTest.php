<?php

namespace Tests\Feature\Docs;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);
        $this->user->assignRole('owner');
    }

    public function test_it_shows_api_docs(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/api/docs');
        $response->assertStatus(200);
    }

    public function test_it_returns_openapi_yaml(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/api/docs/openapi.yaml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/x-yaml');
    }

    public function test_it_returns_openapi_json(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/api/docs/openapi.json');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }
}
