<?php

namespace Tests\Feature\Dashboard;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_it_shows_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.index');
        $response->assertViewHas('stats');
        $response->assertViewHas('quotas');
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_it_requires_agency(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertForbidden();
    }
}
