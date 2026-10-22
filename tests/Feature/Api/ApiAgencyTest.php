<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAgencyTest extends TestCase
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

    public function test_it_shows_settings(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/agency/settings');

        $response->assertOk();
    }

    public function test_it_updates_settings(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/agency/settings', [
            'name' => 'Updated Agency',
            'website' => 'https://example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('agencies', ['id' => $this->agency->id, 'name' => 'Updated Agency']);
    }

    public function test_it_shows_team(): void
    {
        User::factory()->count(2)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/agency/team');

        $response->assertOk();
    }

    public function test_it_shows_billing(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/agency/billing');

        $response->assertOk();
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/agency/settings');

        $response->assertUnauthorized();
    }
}
