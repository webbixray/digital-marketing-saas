<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAnalyticsTest extends TestCase
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

    public function test_cross_platform_stats(): void
    {
        SocialPost::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
        ]);

        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analytics/cross-platform');

        $response->assertStatus(200)
            ->assertJsonPath('data.facebook.total_posts', 5)
            ->assertJsonPath('data.instagram.total_posts', 3);
    }

    public function test_platform_stats(): void
    {
        SocialPost::factory()->count(10)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'tiktok',
            'status' => 'published',
            'views_count' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analytics/platform/tiktok');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_posts', 10)
            ->assertJsonPath('data.total_views', 1000);
    }

    public function test_best_platform(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'engagement_rate' => 5.5,
        ]);

        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
            'status' => 'published',
            'engagement_rate' => 2.0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analytics/best-platform');

        $response->assertStatus(200)
            ->assertJsonPath('data.platform', 'facebook');
    }
}
