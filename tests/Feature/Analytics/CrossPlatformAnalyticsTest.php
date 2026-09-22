<?php

namespace Tests\Feature\Analytics;

use App\Models\Agency;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CrossPlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        Cache::flush();
    }

    /** @test */
    public function test_it_shows_cross_platform_analytics_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertViewIs('analytics.v2.index');
    }

    /** @test */
    public function test_it_requires_authentication(): void
    {
        $response = $this->get(route('analytics.cross-platform'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function test_it_returns_platform_comparison_data(): void
    {
        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'] as $platform) {
            SocialPost::factory()->count(3)->create([
                'agency_id' => $this->agency->id,
                'platform' => $platform,
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'engagement_rate' => rand(100, 5000) / 100,
                'views_count' => rand(1000, 10000),
                'likes_count' => rand(50, 500),
                'comments_count' => rand(10, 100),
                'shares_count' => rand(5, 50),
                'clicks_count' => rand(20, 200),
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertViewHas('platforms');

        $platforms = $response->viewData('platforms');
        $this->assertCount(6, $platforms);
        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'] as $platform) {
            $this->assertArrayHasKey($platform, $platforms);
        }
    }

    /** @test */
    public function test_it_shows_correct_metrics_per_platform(): void
    {
        SocialPost::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'engagement_rate' => 3.5,
            'views_count' => 5000,
            'likes_count' => 200,
            'comments_count' => 50,
            'shares_count' => 25,
            'clicks_count' => 100,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $facebook = $platforms['facebook'];

        $this->assertEquals(5, $facebook['total_posts']);
        $this->assertEquals(5, $facebook['published']);
        $this->assertEquals(3.5, $facebook['avg_engagement_rate']);
        $this->assertEquals(25000, $facebook['total_impressions']);
        $this->assertEquals(1375, $facebook['total_reach']);
        $this->assertEquals(500, $facebook['total_clicks']);
        $this->assertEquals(1000, $facebook['total_likes']);
        $this->assertEquals(250, $facebook['total_comments']);
        $this->assertEquals(125, $facebook['total_shares']);
    }

    /** @test */
    public function test_it_returns_traffic_by_platform_data(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'views_count' => 10000,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'views_count' => 5000,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $traffic = $response->viewData('traffic_by_platform');

        $this->assertEquals(10000, $traffic['facebook']['impressions']);
        $this->assertEquals(5000, $traffic['instagram']['impressions']);
        $this->assertEqualsWithDelta(66.7, $traffic['facebook']['percentage'], 0.1);
        $this->assertEqualsWithDelta(33.3, $traffic['instagram']['percentage'], 0.1);
    }

    /** @test */
    public function test_it_returns_engagement_trend_data(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'engagement_rate' => 4.5,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'engagement_rate' => 3.2,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertViewHas('engagement_trend');
        $trend = $response->viewData('engagement_trend');
        $this->assertIsArray($trend);
        $this->assertNotEmpty($trend);
    }

    /** @test */
    public function test_it_returns_heatmap_data_with_tiers(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'engagement_rate' => 4.5,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'engagement_rate' => 2.5,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'engagement_rate' => 0.5,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertViewHas('heatmap');
        $heatmap = $response->viewData('heatmap');

        $this->assertEquals(1, $heatmap['facebook']['excellent']);
        $this->assertEquals(1, $heatmap['facebook']['good']);
        $this->assertEquals(1, $heatmap['facebook']['low']);
        $this->assertEquals(0, $heatmap['facebook']['average']);
    }

    /** @test */
    public function test_it_returns_demographics_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $demographics = $response->viewData('demographics');

        $this->assertArrayHasKey('age_groups', $demographics);
        $this->assertArrayHasKey('gender', $demographics);
        $this->assertArrayHasKey('top_locations', $demographics);
        $this->assertCount(5, $demographics['age_groups']);
        $this->assertCount(3, $demographics['gender']);
        $this->assertCount(5, $demographics['top_locations']);
    }

    /** @test */
    public function test_it_filters_by_date_range(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(10),
            'views_count' => 5000,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'views_count' => 3000,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform', ['range' => 5]));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $this->assertEquals(3000, $platforms['facebook']['total_impressions']);
    }

    /** @test */
    public function test_it_prevents_accessing_other_agency_data(): void
    {
        $otherAgency = Agency::factory()->create();
        SocialPost::factory()->count(10)->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'views_count' => 10000,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $this->assertEquals(0, $platforms['facebook']['total_posts']);
    }

    /** @test */
    public function test_it_caches_results_per_agency(): void
    {
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        $this->actingAs($this->user)->get(route('analytics.cross-platform'));

        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $platforms = $response->viewData('platforms');
        $this->assertEquals(2, $platforms['facebook']['total_posts']);
    }

    /** @test */
    public function test_it_shows_zero_metrics_for_platforms_with_no_posts(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');

        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'] as $platform) {
            $this->assertEquals(0, $platforms[$platform]['total_posts']);
            $this->assertEquals(0, $platforms[$platform]['total_impressions']);
            $this->assertEquals(0, $platforms[$platform]['total_reach']);
        }
    }

    /** @test */
    public function test_it_only_counts_published_posts_for_metrics(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'engagement_rate' => 5.0,
            'views_count' => 10000,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'draft',
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $this->assertEquals(3, $platforms['facebook']['total_posts']);
        $this->assertEquals(1, $platforms['facebook']['published']);
        $this->assertEquals(5.0, $platforms['facebook']['avg_engagement_rate']);
        $this->assertEquals(10000, $platforms['facebook']['total_impressions']);
    }

    /** @test */
    public function test_it_shows_total_impressions(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'views_count' => 5000,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'views_count' => 3000,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertViewHas('total_impressions', 8000);
    }

    /** @test */
    public function test_it_shows_date_range_in_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform', ['range' => 7]));
        $response->assertOk();
        $response->assertViewHas('date_range', 7);
    }

    /** @test */
    public function test_it_displays_platform_icons_in_comparison_table(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('fab fa-facebook');
        $response->assertSee('fab fa-instagram');
        $response->assertSee('fab fa-x-twitter');
        $response->assertSee('fab fa-linkedin');
        $response->assertSee('fab fa-tiktok');
        $response->assertSee('fab fa-pinterest');
    }

    /** @test */
    public function test_it_includes_chart_js_cdn(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('chart.js');
    }

    /** @test */
    public function test_it_shows_range_selector_buttons(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('7d');
        $response->assertSee('30d');
        $response->assertSee('90d');
    }

    /** @test */
    public function test_it_calculates_reach_as_sum_of_likes_comments_shares(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'likes_count' => 100,
            'comments_count' => 50,
            'shares_count' => 25,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $this->assertEquals(175, $platforms['facebook']['total_reach']);
    }

    /** @test */
    public function test_it_shows_engagement_rate_with_two_decimals(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'engagement_rate' => 3.456,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $this->assertEquals(3.46, $platforms['facebook']['avg_engagement_rate']);
    }

    /** @test */
    public function test_it_shows_chart_canvas_elements(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('trafficPieChart');
        $response->assertSee('engagementTrendChart');
        $response->assertSee('ageChart');
        $response->assertSee('genderChart');
    }

    /** @test */
    public function test_it_shows_correct_cache_key_format(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $this->assertTrue(Cache::has("analytics:v2:{$this->agency->id}:cross_platform:30"));
    }

    /** @test */
    public function test_it_caches_different_ranges_separately(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        $this->actingAs($this->user)->get(route('analytics.cross-platform', ['range' => 7]));
        $this->actingAs($this->user)->get(route('analytics.cross-platform', ['range' => 30]));
        $this->actingAs($this->user)->get(route('analytics.cross-platform', ['range' => 90]));

        $this->assertTrue(Cache::has("analytics:v2:{$this->agency->id}:cross_platform:7"));
        $this->assertTrue(Cache::has("analytics:v2:{$this->agency->id}:cross_platform:30"));
        $this->assertTrue(Cache::has("analytics:v2:{$this->agency->id}:cross_platform:90"));
    }

    /** @test */
    public function test_it_shows_platform_performance_comparison_title(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Platform Performance Comparison');
    }

    /** @test */
    public function test_it_shows_best_performing_content_heatmap_title(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Best Performing Content Heatmap');
    }

    /** @test */
    public function test_it_shows_audience_demographics_section(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Age Distribution');
        $response->assertSee('Gender Split');
        $response->assertSee('Top Locations');
    }

    /** @test */
    public function test_it_shows_traffic_by_platform_title(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Traffic by Platform');
    }

    /** @test */
    public function test_it_shows_engagement_trend_title(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Engagement Trend');
    }

    /** @test */
    public function test_it_handles_empty_database_gracefully(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $platforms = $response->viewData('platforms');
        $this->assertCount(6, $platforms);
        $traffic = $response->viewData('traffic_by_platform');
        $this->assertCount(6, $traffic);
        $heatmap = $response->viewData('heatmap');
        $this->assertCount(6, $heatmap);
    }

    /** @test */
    public function test_it_shows_correct_route_name(): void
    {
        $response = $this->actingAs($this->user)->get('/analytics/v2');
        $response->assertOk();
        $this->assertEquals('/analytics/v2', parse_url(route('analytics.cross-platform'), PHP_URL_PATH));
    }

    /** @test */
    public function test_it_shows_table_headers(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Platform');
        $response->assertSee('Posts');
        $response->assertSee('Published');
        $response->assertSee('Avg Engagement');
        $response->assertSee('Impressions');
        $response->assertSee('Reach');
        $response->assertSee('Clicks');
        $response->assertSee('Likes');
        $response->assertSee('Shares');
    }

    /** @test */
    public function test_it_shows_percentage_in_traffic_data(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'views_count' => 7500,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'views_count' => 2500,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $traffic = $response->viewData('traffic_by_platform');
        $this->assertEquals(75.0, $traffic['facebook']['percentage']);
        $this->assertEquals(25.0, $traffic['instagram']['percentage']);
    }

    /** @test */
    public function test_it_shows_heatmap_performance_tier_labels(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Excellent (≥4%)');
        $response->assertSee('Good (2-4%)');
        $response->assertSee('Average (1-2%)');
        $response->assertSee('Low (<1%)');
    }

    /** @test */
    public function test_it_shows_cross_platform_analytics_title(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Cross-Platform Analytics 2.0');
    }

    /** @test */
    public function test_it_shows_unified_performance_description(): void
    {
        $response = $this->actingAs($this->user)->get(route('analytics.cross-platform'));
        $response->assertOk();
        $response->assertSee('Unified performance across Facebook, Instagram, Twitter/X, LinkedIn, TikTok, Pinterest');
    }
}
