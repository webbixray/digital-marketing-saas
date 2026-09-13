<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;
    private SocialAccount $socialAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->socialAccount = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
        ]);
    }

    // ─── Auth Middleware ───────────────────────────────────────────────

    public function test_calendar_index_requires_authentication(): void
    {
        $response = $this->get(route('calendar.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_calendar_events_requires_authentication(): void
    {
        $response = $this->get(route('calendar.events'));

        $response->assertRedirect(route('login'));
    }

    // ─── Agency Middleware ─────────────────────────────────────────────

    public function test_calendar_index_requires_agency_assignment(): void
    {
        $userWithoutAgency = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($userWithoutAgency)->get(route('calendar.index'));

        $response->assertForbidden();
    }

    public function test_calendar_events_requires_agency_assignment(): void
    {
        $userWithoutAgency = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($userWithoutAgency)->get(route('calendar.events'));

        $response->assertForbidden();
    }

    public function test_calendar_events_api_returns_401_for_unauthenticated_json(): void
    {
        $response = $this->json('GET', route('calendar.events'));

        $response->assertUnauthorized();
    }

    // ─── Calendar Index ────────────────────────────────────────────────

    public function test_calendar_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar.index'));

        $response->assertOk();
        $response->assertViewIs('calendar.index');
    }

    public function test_calendar_index_returns_view_with_expected_data(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar.index'));

        $response->assertOk();
        $response->assertViewHas('events');
        $response->assertViewHas('stats');
        $response->assertViewHas('bestTimes');
        $response->assertViewHas('year');
        $response->assertViewHas('month');
    }

    public function test_calendar_index_accepts_year_and_month_parameters(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendar.index', [
            'year' => 2025,
            'month' => 6,
        ]));

        $response->assertOk();
        $response->assertViewHas('year', 2025);
        $response->assertViewHas('month', 6);
    }

    // ─── Calendar Events API ───────────────────────────────────────────

    public function test_calendar_events_api_returns_json(): void
    {
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('calendar.events'));

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'content',
                'start',
                'end',
                'platform',
                'status',
                'color',
                'textColor',
                'extendedProps',
            ],
        ]);
    }

    public function test_calendar_events_filters_by_date_range(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'scheduled_at' => now()->subDays(10),
        ]);

        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        $start = now()->toDateString();
        $end = now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson(route('calendar.events', ['start' => $start, 'end' => $end]));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
    }

    public function test_calendar_events_only_returns_current_agency_posts(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherAccount = SocialAccount::factory()->create(['agency_id' => $otherAgency->id]);

        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
            'social_account_id' => $otherAccount->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('calendar.events'));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($this->agency->id, SocialPost::find($data[0]['id'])->agency_id);
    }

    // ─── Scheduled Posts Display ───────────────────────────────────────

    public function test_scheduled_posts_appear_in_calendar_index(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar.index'));

        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(3, $events);
    }

    public function test_scheduled_posts_appear_in_events_api(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(1),
            'content' => 'Upcoming scheduled post',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('calendar.events'));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Upcoming scheduled post', $data[0]['content']);
        $this->assertEquals('scheduled', $data[0]['status']);
    }

    public function test_calendar_index_shows_correct_stats(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(1),
        ]);

        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'status' => 'draft',
            'scheduled_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar.index'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['scheduled']);
        $this->assertEquals(1, $stats['draft']);
    }

    public function test_calendar_index_shows_best_posting_times(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'status' => 'published',
            'published_at' => now()->setHour(14),
            'likes_count' => 50,
            'comments_count' => 10,
            'shares_count' => 5,
        ]);

        $response = $this->actingAs($this->user)->get(route('calendar.index'));

        $response->assertOk();
        $bestTimes = $response->viewData('bestTimes');
        $this->assertIsArray($bestTimes);
        $this->assertNotEmpty($bestTimes);
        $this->assertEquals('14:00', $bestTimes[0]['label']);
    }
}
