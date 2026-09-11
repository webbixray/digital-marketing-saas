<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
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

    public function test_it_shows_analytics_page(): void
    {
        $response = $this->get('/analytics');
        $response->assertStatus(200);
    }

    public function test_it_returns_post_stats(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
        ]);
        $response = $this->get('/analytics');
        $response->assertStatus(200);
        $response->assertViewHas('postStats');
        $postStats = $response->viewData('postStats');
        $this->assertEquals(3, $postStats['total_posts']);
    }

    public function test_it_returns_campaign_stats(): void
    {
        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        $response = $this->get('/analytics');
        $response->assertStatus(200);
        $response->assertViewHas('campaignStats');
        $campaignStats = $response->viewData('campaignStats');
        $this->assertEquals(2, $campaignStats['total']);
    }

    public function test_it_returns_client_stats(): void
    {
        Client::factory()->count(4)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/analytics');
        $response->assertStatus(200);
        $response->assertViewHas('clientStats');
        $clientStats = $response->viewData('clientStats');
        $this->assertEquals(4, $clientStats['total']);
    }

    public function test_it_filters_by_date_range(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'created_at' => now()->subDays(10),
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'created_at' => now(),
        ]);
        $response = $this->get('/analytics?range=5');
        $response->assertStatus(200);
    }

    public function test_it_prevents_accessing_other_agency_analytics(): void
    {
        $otherAgency = Agency::factory()->create();
        SocialPost::factory()->count(5)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/analytics');
        $response->assertStatus(200);
        $postStats = $response->viewData('postStats');
        $this->assertEquals(0, $postStats['total_posts']);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/analytics');
        $response->assertRedirect('/login');
    }

    public function test_it_caches_analytics_data(): void
    {
        SocialPost::factory()->count(2)->create(['agency_id' => $this->agency->id]);
        $response1 = $this->get('/analytics');
        $response1->assertStatus(200);
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response2 = $this->get('/analytics');
        $response2->assertStatus(200);
        $postStats = $response2->viewData('postStats');
        $this->assertEquals(2, $postStats['total_posts']);
    }
}
