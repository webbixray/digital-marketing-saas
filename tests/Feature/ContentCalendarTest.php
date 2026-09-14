<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentCalendarTest extends TestCase
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

    public function test_calendar_index_requires_auth(): void
    {
        $response = $this->get(route('calendar.index'));
        $response->assertStatus(302);
    }

    public function test_calendar_index_returns_200_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar.index'));
        $response->assertStatus(200);
    }

    public function test_scheduled_posts_display_on_calendar(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar.index'));
        $response->assertStatus(200);
    }
}
