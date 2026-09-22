<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\OptimalPostingTime;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agency $agency;
    protected SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        $this->account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
            'platform_username' => 'testuser',
            'platform_display_name' => 'Test User',
            'is_active' => true,
        ]);
    }

    public function test_calendar_route_exists(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
    }

    public function test_calendar_events_route_exists(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]));
        $response->assertStatus(200);
    }

    public function test_calendar_index_requires_auth(): void
    {
        $response = $this->get(route('calendar'));
        $response->assertStatus(302);
    }

    public function test_calendar_events_requires_auth(): void
    {
        $response = $this->get(route('calendar.events'));
        $response->assertStatus(302);
    }

    public function test_calendar_index_requires_agency(): void
    {
        $userWithoutAgency = User::factory()->create(['agency_id' => null]);
        $response = $this->actingAs($userWithoutAgency)->get(route('calendar'));
        // Agency middleware may return 403 or 302 depending on config
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    public function test_calendar_index_returns_200_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertViewIs('calendar.index');
    }

    public function test_calendar_index_contains_fullcalendar_cdn(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('fullcalendar@6', false);
    }

    public function test_calendar_index_contains_view_toolbar(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('dayGridMonth', false);
        $response->assertSee('timeGridWeek', false);
        $response->assertSee('timeGridDay', false);
    }

    public function test_calendar_index_contains_optimal_slots_section(): void
    {
        OptimalPostingTime::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
            'day_of_week' => 1,
            'hour' => 14,
            'engagement_score' => 85.5,
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('Suggested Optimal Posting Times', false);
    }

    public function test_calendar_index_contains_platform_filter(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('Platform:', false);
        $response->assertSee('All Platforms', false);
    }

    public function test_calendar_index_contains_account_filter(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('Account:', false);
        $response->assertSee('All Accounts', false);
    }

    public function test_calendar_index_contains_stats_cards(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('Total Posts', false);
        $response->assertSee('Published', false);
        $response->assertSee('Scheduled', false);
        $response->assertSee('Failed', false);
        $response->assertSee('Best Times', false);
    }

    public function test_calendar_index_contains_drag_drop_hint(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertSee('Drag and drop events to reschedule', false);
    }

    public function test_events_returns_scheduled_posts_as_json(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Test scheduled post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $post->id,
            'platform' => 'twitter',
            'status' => 'scheduled',
        ]);
    }

    public function test_events_filters_by_platform(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Twitter post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $facebookAccount = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'is_active' => true,
        ]);

        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $facebookAccount->id,
            'platform' => 'facebook',
            'content' => 'Facebook post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
            'platform' => 'twitter',
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['platform' => 'twitter']);
    }

    public function test_events_filters_by_account(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Account post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
            'account_id' => $this->account->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['platform' => 'twitter']);
    }

    public function test_events_returns_empty_for_other_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'twitter',
            'content' => 'Other agency post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_events_contains_extended_props(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Test post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('extendedProps', $data[0]);
        $this->assertArrayHasKey('platform', $data[0]['extendedProps']);
        $this->assertArrayHasKey('status', $data[0]['extendedProps']);
        $this->assertArrayHasKey('account', $data[0]['extendedProps']);
        $this->assertArrayHasKey('edit_url', $data[0]['extendedProps']);
    }

    public function test_events_color_coded_by_status(): void
    {
        $scheduledPost = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Scheduled post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $publishedPost = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Published post',
            'status' => 'published',
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        $scheduled = collect($data)->firstWhere('id', $scheduledPost->id);
        $published = collect($data)->firstWhere('id', $publishedPost->id);

        $this->assertEquals('#3b82f6', $scheduled['color']); // blue for scheduled
        $this->assertEquals('#10b981', $published['color']); // green for published
    }

    public function test_events_editable_for_scheduled_and_draft(): void
    {
        $scheduledPost = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Scheduled post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $publishedPost = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Published post',
            'status' => 'published',
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
        ]));

        $data = $response->json();
        $scheduled = collect($data)->firstWhere('id', $scheduledPost->id);
        $published = collect($data)->firstWhere('id', $publishedPost->id);

        $this->assertTrue($scheduled['editable']);
        $this->assertFalse($published['editable']);
    }

    public function test_calendar_slot_metadata_stored(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Test post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'calendar_slot' => [
                'is_optimal' => true,
                'suggested_hour' => 14,
                'day_of_week' => 1,
                'score' => 92.5,
            ],
        ]);

        $this->assertDatabaseHas('social_posts', [
            'id' => $post->id,
        ]);

        $freshPost = SocialPost::find($post->id);
        $this->assertIsArray($freshPost->calendar_slot);
        $this->assertTrue($freshPost->calendar_slot['is_optimal']);
        $this->assertEquals(14, $freshPost->calendar_slot['suggested_hour']);
    }

    public function test_calendar_slot_nullable(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
            'content' => 'Test post',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'calendar_slot' => null,
        ]);

        $freshPost = SocialPost::find($post->id);
        $this->assertNull($freshPost->calendar_slot);
    }

    public function test_best_times_use_optimal_posting_time_model(): void
    {
        OptimalPostingTime::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
            'day_of_week' => 1,
            'hour' => 14,
            'engagement_score' => 95.0,
        ]);
        OptimalPostingTime::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
            'day_of_week' => 3,
            'hour' => 10,
            'engagement_score' => 88.5,
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertViewHas('bestTimes', function ($bestTimes) {
            return count($bestTimes) === 2
                && $bestTimes[0]['hour'] === 14
                && $bestTimes[0]['day'] === 'Monday'
                && $bestTimes[0]['platform'] === 'twitter';
        });
    }

    public function test_optimal_slots_from_model(): void
    {
        OptimalPostingTime::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
            'day_of_week' => 1,
            'hour' => 14,
            'engagement_score' => 95.0,
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertViewHas('optimalSlots', function ($slots) {
            return count($slots) >= 1
                && $slots[0]['day_name'] === 'Monday'
                && $slots[0]['time_slot'] === '14:00'
                && $slots[0]['platform'] === 'twitter';
        });
    }

    public function test_calendar_route_name_is_calendar(): void
    {
        $this->assertEquals(
            url('/calendar'),
            route('calendar')
        );
    }

    public function test_calendar_events_route_name_is_calendar_events(): void
    {
        $this->assertEquals(
            url('/calendar/events'),
            route('calendar.events')
        );
    }

    public function test_calendar_uses_unified_layout(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar'));
        $response->assertStatus(200);
        $response->assertViewIs('calendar.index');
        // Verify it uses the unified layout by checking for the sidebar nav structure
        $response->assertSee('nav-link', false);
        $response->assertSee('sidebar-nav', false);
    }
}
