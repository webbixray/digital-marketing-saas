<?php

namespace Tests\Feature\ActivityLog;

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
    }

    public function test_it_lists_logs(): void
    {
        ActivityLog::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('activity.index'));

        $response->assertOk();
        $response->assertViewIs('activity.index');
        $response->assertViewHas('logs');
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('activity.index'));

        $response->assertRedirect(route('login'));
    }
}
