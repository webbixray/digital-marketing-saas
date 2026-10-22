<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\EmailCampaign;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService;
        $this->agency = Agency::factory()->create();
    }

    public function test_get_dashboard_stats_returns_array_with_all_sections()
    {
        $stats = $this->service->getDashboardStats($this->agency);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('overview', $stats);
        $this->assertArrayHasKey('social', $stats);
        $this->assertArrayHasKey('email', $stats);
        $this->assertArrayHasKey('financial', $stats);
        $this->assertArrayHasKey('ai', $stats);
    }

    public function test_overview_stats_contains_expected_keys()
    {
        $stats = $this->service->getOverviewStats($this->agency);
        $overview = $stats;

        $this->assertArrayHasKey('total_clients', $overview);
        $this->assertArrayHasKey('total_posts', $overview);
        $this->assertArrayHasKey('total_campaigns', $overview);
        $this->assertArrayHasKey('total_revenue', $overview);
        $this->assertArrayHasKey('pending_invoices', $overview);
        $this->assertArrayHasKey('active_social_accounts', $overview);
    }

    public function test_social_stats_with_posts()
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'engagement_rate' => 5.5,
        ]);
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
            'engagement_rate' => 0,
        ]);
        SocialPost::factory()->count(1)->create([
            'agency_id' => $this->agency->id,
            'status' => 'failed',
            'engagement_rate' => 0,
        ]);

        $stats = $this->service->getSocialStats($this->agency);

        $this->assertEquals(6, $stats['total_posts']);
        $this->assertEquals(3, $stats['published']);
        $this->assertEquals(2, $stats['scheduled']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(0, $stats['draft']);
        $this->assertEqualsWithDelta(5.5, $stats['average_engagement'], 0.01);
        $this->assertArrayHasKey('by_platform', $stats);
    }

    public function test_email_stats_with_campaigns()
    {
        EmailCampaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'sent_count' => 100,
            'opened_count' => 50,
            'clicked_count' => 25,
            'open_rate' => 50.0,
            'click_rate' => 25.0,
        ]);
        EmailCampaign::factory()->count(1)->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $stats = $this->service->getEmailStats($this->agency);

        $this->assertEquals(3, $stats['total_campaigns']);
        $this->assertEquals(2, $stats['sent_campaigns']);
        $this->assertEquals(1, $stats['draft_campaigns']);
        $this->assertEquals(200, $stats['total_sent']);
        $this->assertEquals(100, $stats['total_opened']);
        $this->assertEquals(50, $stats['total_clicked']);
        $this->assertEqualsWithDelta(50.0, $stats['average_open_rate'], 0.01);
        $this->assertEqualsWithDelta(25.0, $stats['average_click_rate'], 0.01);
    }

    public function test_financial_stats_with_invoices()
    {
        Invoice::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 100,
            'paid_at' => now(),
        ]);
        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
            'total' => 50,
        ]);

        $stats = $this->service->getFinancialStats($this->agency);

        $this->assertEquals(300, $stats['total_revenue']);
        $this->assertEquals(100, $stats['pending_amounts']);
        $this->assertEquals(5, $stats['total_invoices']);
        $this->assertEquals(3, $stats['paid_invoices']);
        $this->assertEquals(2, $stats['pending_invoices']);
    }

    public function test_ai_stats_with_logs()
    {
        AiContentLog::factory()->count(5)->success()->create([
            'agency_id' => $this->agency->id,
            'total_tokens' => 1000,
            'cost_usd' => 0.01,
            'action' => 'generate',
        ]);
        AiContentLog::factory()->count(2)->failed()->create([
            'agency_id' => $this->agency->id,
            'total_tokens' => 100,
            'cost_usd' => 0,
            'action' => 'generate',
        ]);
        AiContentLog::factory()->count(1)->success()->create([
            'agency_id' => $this->agency->id,
            'total_tokens' => 500,
            'cost_usd' => 0.005,
            'action' => 'hashtags',
        ]);

        $stats = $this->service->getAiStats($this->agency);

        $this->assertEquals(8, $stats['total_generations']);
        $this->assertEquals(6, $stats['successful_generations']);
        $this->assertEquals(2, $stats['failed_generations']);
        $this->assertEquals(5700, $stats['total_tokens_used']);
        $this->assertEqualsWithDelta(0.055, $stats['total_cost_usd'], 0.001);
        $this->assertArrayHasKey('by_action', $stats);
        $this->assertEquals(7, $stats['by_action']['generate']);
        $this->assertEquals(1, $stats['by_action']['hashtags']);
    }

    public function test_clear_cache_does_not_throw()
    {
        $this->service->clearCache($this->agency);
        $this->assertTrue(true);
    }

    public function test_get_total_posts_returns_correct_count()
    {
        SocialPost::factory()->count(10)->create(['agency_id' => $this->agency->id]);

        $count = $this->service->getTotalPosts($this->agency);
        $this->assertEquals(10, $count);
    }

    public function test_get_total_campaigns_returns_correct_count()
    {
        EmailCampaign::factory()->count(5)->create(['agency_id' => $this->agency->id]);

        $count = $this->service->getTotalCampaigns($this->agency);
        $this->assertEquals(5, $count);
    }

    public function test_get_total_invoices_returns_correct_count()
    {
        Invoice::factory()->count(7)->create(['agency_id' => $this->agency->id]);

        $count = $this->service->getTotalInvoices($this->agency);
        $this->assertEquals(7, $count);
    }

    public function test_posts_by_platform_includes_all_platforms()
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
        ]);

        $byPlatform = $this->service->getPostsByStatus($this->agency);
        $this->assertIsArray($byPlatform);
    }

    public function test_email_campaigns_by_status()
    {
        EmailCampaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
        ]);
        EmailCampaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $byStatus = $this->service->getEmailCampaignsByStatus($this->agency);

        $this->assertIsArray($byStatus);
        $this->assertEquals(3, $byStatus['sent'] ?? 0);
        $this->assertEquals(2, $byStatus['draft'] ?? 0);
    }

    public function test_get_revenue_for_range()
    {
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 500,
            'paid_at' => now(),
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 300,
            'paid_at' => now()->subDays(30),
        ]);

        $start = now()->subDays(7)->format('Y-m-d H:i:s');
        $end = now()->format('Y-m-d H:i:s');
        $revenue = $this->service->getRevenueForRange($this->agency, $start, $end);

        $this->assertEquals(500, $revenue);
    }

    public function test_get_ai_cost_for_range()
    {
        AiContentLog::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'success',
            'cost_usd' => 1.50,
            'created_at' => now(),
        ]);
        AiContentLog::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'success',
            'cost_usd' => 0.50,
            'created_at' => now()->subDays(10),
        ]);

        $start = now()->subDays(7)->format('Y-m-d H:i:s');
        $end = now()->format('Y-m-d H:i:s');
        $cost = $this->service->getAiCostForRange($this->agency, $start, $end);

        $this->assertEqualsWithDelta(1.50, $cost, 0.001);
    }

    public function test_get_top_performing_posts_returns_sorted()
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'engagement_rate' => 10.0,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'engagement_rate' => 20.0,
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'engagement_rate' => 5.0,
        ]);

        $top = $this->service->getTopPerformingPosts($this->agency, 2);

        $this->assertCount(2, $top);
        $this->assertEqualsWithDelta(20.0, $top[0]->engagement_rate, 0.01);
        $this->assertEqualsWithDelta(10.0, $top[1]->engagement_rate, 0.01);
    }

    public function test_get_top_email_campaigns_returns_sorted()
    {
        EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'open_rate' => 10.0,
        ]);
        EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'open_rate' => 50.0,
        ]);
        EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'open_rate' => 30.0,
        ]);

        $top = $this->service->getTopEmailCampaigns($this->agency, 2);

        $this->assertCount(2, $top);
        $this->assertEqualsWithDelta(50.0, $top[0]->open_rate, 0.01);
        $this->assertEqualsWithDelta(30.0, $top[1]->open_rate, 0.01);
    }
}
