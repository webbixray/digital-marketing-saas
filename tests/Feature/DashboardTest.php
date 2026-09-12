<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\LandingPage;
use App\Models\SocialPost;
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
        $this->actingAs($this->user);
    }

    public function test_it_shows_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('quotas');
    }

    public function test_it_shows_post_stats(): void
    {
        SocialPost::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
        ]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_posts', $stats);
        $this->assertEquals(5, $stats['total_posts']);
    }

    public function test_it_shows_quota_info(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $quotas = $response->viewData('quotas');
        $this->assertArrayHasKey('posts', $quotas);
        $this->assertArrayHasKey('ai', $quotas);
        $this->assertArrayHasKey('campaigns', $quotas);
    }

    public function test_it_shows_recent_activity(): void
    {
        ActivityLog::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $recentActivity = $response->viewData('recentActivity');
        $this->assertCount(3, $recentActivity);
    }

    public function test_it_shows_upcoming_scheduled_posts(): void
    {
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(3),
        ]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $upcomingPosts = $response->viewData('upcomingPosts');
        $this->assertCount(2, $upcomingPosts);
    }

    public function test_it_shows_landing_page_count(): void
    {
        LandingPage::factory()->count(4)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $quotas = $response->viewData('quotas');
        $this->assertArrayHasKey('landing_pages', $quotas);
    }

    public function test_it_prevents_accessing_other_agency_dashboard(): void
    {
        $otherAgency = Agency::factory()->create();
        SocialPost::factory()->count(5)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['total_posts']);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_it_shows_ai_generation_count(): void
    {
        AiContentLog::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $quotas = $response->viewData('quotas');
        $this->assertArrayHasKey('ai', $quotas);
    }
}
