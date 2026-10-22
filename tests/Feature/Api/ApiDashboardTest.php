<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDashboardTest extends TestCase
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

    public function test_it_returns_dashboard_stats(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertOk();
        $response->assertJsonStructure(['overview', 'social', 'ai']);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    }
}
