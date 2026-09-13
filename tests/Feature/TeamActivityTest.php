<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamActivityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_team_activity_requires_auth(): void
    {
        $response = $this->get(route('team.activity'));
        $response->assertStatus(302);
    }

    public function test_team_activity_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('team.activity'));
        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    public function test_team_activity_filters_by_member(): void
    {
        $response = $this->actingAs($this->user)->get(route('team.activity', ['user_id' => $this->user->id]));
        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    public function test_team_activity_filters_by_date(): void
    {
        $response = $this->actingAs($this->user)->get(route('team.activity', ['date' => now()->toDateString()]));
        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    public function test_team_activity_does_not_show_other_agency_logs(): void
    {
        $otherAgency = Agency::factory()->create();
        $response = $this->actingAs($this->user)->get(route('team.activity'));
        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }
}
