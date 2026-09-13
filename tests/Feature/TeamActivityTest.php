<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamActivityTest extends TestCase
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

    public function test_team_activity_requires_authentication(): void
    {
        $response = $this->get(route('team.activity'));

        $response->assertRedirect(route('login'));
    }

    public function test_team_activity_requires_agency(): void
    {
        $userWithoutAgency = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($userWithoutAgency)->get(route('team.activity'));

        $response->assertForbidden();
    }

    public function test_team_activity_index_loads_successfully(): void
    {
        ActivityLog::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('team.activity'));

        $response->assertOk();
        $response->assertViewIs('activity.index');
        $response->assertViewHas('activities');
        $response->assertViewHas('stats');
    }

    public function test_team_activity_filters_by_member(): void
    {
        $member = User::factory()->create(['agency_id' => $this->agency->id]);

        ActivityLog::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $member->id,
        ]);

        ActivityLog::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('team.activity', ['user_id' => $member->id]));

        $response->assertOk();
        $response->assertViewIs('activity.index');
        $response->assertViewHas('activities');
    }

    public function test_team_activity_filters_by_date(): void
    {
        ActivityLog::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'created_at' => now()->subDays(10),
        ]);

        ActivityLog::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('team.activity', [
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertViewIs('activity.index');
        $response->assertViewHas('activities');
    }

    public function test_team_activity_does_not_show_other_agency_logs(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        ActivityLog::factory()->count(3)->create([
            'agency_id' => $otherAgency->id,
            'user_id' => $otherUser->id,
        ]);

        ActivityLog::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('team.activity'));

        $response->assertOk();
        $activities = $response->viewData('activities');
        $this->assertCount(2, $activities);
    }
}
