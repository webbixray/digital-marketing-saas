<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_activity_logs(): void
    {
        ActivityLog::factory()->count(5)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/activity');
        $response->assertStatus(200);
        $response->assertViewHas('logs');
    }

    public function test_it_filters_by_action(): void
    {
        ActivityLog::factory()->create(['agency_id' => $this->agency->id, 'action' => 'created']);
        ActivityLog::factory()->create(['agency_id' => $this->agency->id, 'action' => 'updated']);
        $response = $this->get('/activity?action=created');
        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_it_filters_by_user_id(): void
    {
        $otherUser = User::factory()->create(['agency_id' => $this->agency->id]);
        ActivityLog::factory()->create(['agency_id' => $this->agency->id, 'user_id' => $this->user->id]);
        ActivityLog::factory()->create(['agency_id' => $this->agency->id, 'user_id' => $otherUser->id]);
        $response = $this->get("/activity?user_id={$this->user->id}");
        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_it_filters_by_date_range(): void
    {
        ActivityLog::factory()->create(['agency_id' => $this->agency->id, 'created_at' => now()->subDays(10)]);
        ActivityLog::factory()->create(['agency_id' => $this->agency->id, 'created_at' => now()]);
        $response = $this->get('/activity?date_from='.now()->subDays(5)->toDateString());
        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_it_prevents_accessing_other_agency_logs(): void
    {
        $otherAgency = Agency::factory()->create();
        ActivityLog::factory()->count(3)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/activity');
        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertCount(0, $logs);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/activity');
        $response->assertRedirect('/login');
    }
}
