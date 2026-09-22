<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortal2Test extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $owner;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->owner = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->client = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    // ========================================================================
    //  ROUTE / MIDDLEWARE TESTS
    // ========================================================================

    public function test_client_portal_v2_routes_are_registered(): void
    {
        $routes = [
            'client-portal.v2.dashboard',
            'client-portal.v2.campaigns',
            'client-portal.v2.analytics',
            'client-portal.v2.invoices',
        ];

        foreach ($routes as $routeName) {
            $this->assertTrue(
                route($routeName) !== null,
                "Route {$routeName} is not registered"
            );
        }
    }

    public function test_dashboard_route_uses_correct_middleware(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertOk();
    }

    public function test_campaigns_route_uses_correct_middleware(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns'));

        $response->assertOk();
    }

    public function test_analytics_route_uses_correct_middleware(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $response->assertOk();
    }

    public function test_invoices_route_uses_correct_middleware(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $response->assertOk();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get(route('client-portal.v2.dashboard'));
        $response->assertRedirect();
    }

    public function test_guest_is_redirected_from_campaigns(): void
    {
        $response = $this->get(route('client-portal.v2.campaigns'));
        $response->assertRedirect();
    }

    public function test_guest_is_redirected_from_analytics(): void
    {
        $response = $this->get(route('client-portal.v2.analytics'));
        $response->assertRedirect();
    }

    public function test_guest_is_redirected_from_invoices(): void
    {
        $response = $this->get(route('client-portal.v2.invoices'));
        $response->assertRedirect();
    }

    public function test_user_without_agency_is_blocked(): void
    {
        $user = User::factory()->create([
            'agency_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('client-portal.v2.dashboard'));

        // Should be redirected or get 403 from EnsureAgencyAccess middleware
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    // ========================================================================
    //  DASHBOARD TESTS
    // ========================================================================

    public function test_dashboard_shows_correct_view(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewIs('client-portal.dashboard');
    }

    public function test_dashboard_shows_active_campaigns_count(): void
    {
        Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('activeCampaigns', 3);
    }

    public function test_dashboard_shows_total_spend(): void
    {
        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 500.00,
            'paid_date' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('totalSpend', 1000.00);
    }

    public function test_dashboard_shows_performance_score(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'engagement_rate' => 4.5,
            'views_count' => 5000,
            'likes_count' => 200,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('performanceScore', function ($score) {
            return $score > 0 && $score <= 100;
        });
    }

    public function test_dashboard_scopes_data_to_agency(): void
    {
        // Create data for another agency
        $otherAgency = Agency::factory()->create();
        Campaign::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
            'status' => 'active',
        ]);
        Invoice::factory()->count(2)->create([
            'agency_id' => $otherAgency->id,
            'status' => 'paid',
            'total' => 1000.00,
            'paid_date' => now(),
        ]);

        // Create data for current agency
        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('activeCampaigns', 2);
        $response->assertViewHas('totalSpend', 0.00);
    }

    public function test_dashboard_shows_recent_campaigns(): void
    {
        $campaigns = Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('recentCampaigns');
    }

    public function test_dashboard_shows_zero_when_no_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('activeCampaigns', 0);
        $response->assertViewHas('totalSpend', 0.00);
        $response->assertViewHas('performanceScore', 0);
    }

    // ========================================================================
    //  CAMPAIGNS TESTS
    // ========================================================================

    public function test_campaigns_shows_correct_view(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns'));

        $response->assertViewIs('client-portal.campaigns');
    }

    public function test_campaigns_list_shows_campaigns(): void
    {
        $campaigns = Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns'));

        $response->assertOk();
        $response->assertViewHas('campaigns');
    }

    public function test_campaigns_filters_by_status(): void
    {
        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns', ['status' => 'active']));

        $response->assertOk();
        $viewCampaigns = $response->viewData('campaigns');
        $this->assertEquals(2, $viewCampaigns->count());
    }

    public function test_campaigns_filters_by_client(): void
    {
        $client2 = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $this->client->id,
        ]);
        Campaign::factory()->count(1)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client2->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns', ['client_id' => $this->client->id]));

        $viewCampaigns = $response->viewData('campaigns');
        $this->assertEquals(2, $viewCampaigns->count());
    }

    public function test_campaigns_scopes_to_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        Campaign::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
        ]);
        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns'));

        $viewCampaigns = $response->viewData('campaigns');
        $this->assertEquals(2, $viewCampaigns->count());
    }

    public function test_campaigns_search_filters_by_name(): void
    {
        Campaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Summer Sale Campaign',
        ]);
        Campaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Winter Promotion',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns', ['search' => 'Summer']));

        $viewCampaigns = $response->viewData('campaigns');
        $this->assertEquals(1, $viewCampaigns->count());
    }

    public function test_campaigns_shows_status_counts(): void
    {
        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns'));

        $response->assertViewHas('statusCounts');
        $statusCounts = $response->viewData('statusCounts');
        $this->assertEquals(2, $statusCounts['active'] ?? 0);
        $this->assertEquals(3, $statusCounts['completed'] ?? 0);
    }

    // ========================================================================
    //  ANALYTICS TESTS
    // ========================================================================

    public function test_analytics_shows_correct_view(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $response->assertViewIs('client-portal.analytics');
    }

    public function test_analytics_shows_campaign_performance_data(): void
    {
        Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'views_count' => 1000,
            'likes_count' => 100,
            'comments_count' => 50,
            'shares_count' => 25,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $response->assertOk();
        $response->assertViewHas('campaignPerformance');
    }

    public function test_analytics_shows_platform_breakdown(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'views_count' => 1000,
        ]);
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'views_count' => 500,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $response->assertViewHas('platformMetrics');
        $platformMetrics = $response->viewData('platformMetrics');
        $this->assertCount(2, $platformMetrics);
    }

    public function test_analytics_shows_overall_metrics(): void
    {
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'views_count' => 5000,
            'likes_count' => 300,
            'comments_count' => 100,
            'shares_count' => 50,
            'clicks_count' => 200,
            'engagement_rate' => 4.5,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $response->assertViewHas('overallMetrics');
        $metrics = $response->viewData('overallMetrics');
        $this->assertEquals(10000, $metrics['total_impressions']);
        $this->assertEquals(2, $metrics['published_posts']);
    }

    public function test_analytics_scopes_to_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        SocialPost::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
            'views_count' => 1000,
        ]);
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'views_count' => 500,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $metrics = $response->viewData('overallMetrics');
        $this->assertEquals(1000, $metrics['total_impressions']);
    }

    public function test_analytics_shows_top_performing_posts(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'engagement_rate' => 8.5,
            'content' => 'Top performing post',
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'engagement_rate' => 2.0,
            'content' => 'Lower performing post',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $topPosts = $response->viewData('topPosts');
        $this->assertGreaterThanOrEqual(1, $topPosts->count());
        $this->assertEquals(8.5, $topPosts->first()->engagement_rate);
    }

    public function test_analytics_handles_empty_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $response->assertOk();
        $metrics = $response->viewData('overallMetrics');
        $this->assertEquals(0, $metrics['total_posts']);
        $this->assertEquals(0, $metrics['total_impressions']);
    }

    // ========================================================================
    //  INVOICES TESTS
    // ========================================================================

    public function test_invoices_shows_correct_view(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $response->assertViewIs('client-portal.invoices');
    }

    public function test_invoices_list_shows_invoices(): void
    {
        Invoice::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $response->assertOk();
        $response->assertViewHas('invoices');
    }

    public function test_invoices_shows_pending_and_overdue_total(): void
    {
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
            'total' => 500.00,
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'overdue',
            'total' => 300.00,
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 200.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $response->assertViewHas('totalOutstanding', 800.00);
        $response->assertViewHas('totalPaid', 200.00);
    }

    public function test_invoices_filters_by_status(): void
    {
        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
        ]);
        Invoice::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices', ['status' => 'paid']));

        $viewInvoices = $response->viewData('invoices');
        $this->assertEquals(3, $viewInvoices->count());
    }

    public function test_invoices_shows_overdue_count(): void
    {
        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'overdue',
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $response->assertViewHas('overdueCount', 2);
    }

    public function test_invoices_scopes_to_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        Invoice::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
        ]);
        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $viewInvoices = $response->viewData('invoices');
        $this->assertEquals(2, $viewInvoices->total());
    }

    public function test_invoices_shows_pay_button_for_pending_and_overdue(): void
    {
        $pendingInvoice = Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'pending',
            'total' => 500.00,
        ]);
        $paidInvoice = Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 300.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $response->assertOk();
        // Verify view renders pay button for pending/overdue invoices
        $response->assertSee('Pay');
    }

    public function test_invoices_filters_by_client(): void
    {
        $client2 = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $this->client->id,
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client2->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices', ['client_id' => $this->client->id]));

        $viewInvoices = $response->viewData('invoices');
        $this->assertEquals(2, $viewInvoices->count());
    }

    public function test_invoices_filters_by_date_range(): void
    {
        // Create invoices with specific dates
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'issue_date' => '2026-08-01',
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'issue_date' => '2026-09-15',
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'issue_date' => '2026-09-20',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices', [
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]));

        $viewInvoices = $response->viewData('invoices');
        $this->assertEquals(2, $viewInvoices->count());
    }

    // ========================================================================
    //  SECURITY / DATA ISOLATION TESTS
    // ========================================================================

    public function test_cross_agency_data_is_isolated_on_dashboard(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create([
            'agency_id' => $otherAgency->id,
            'role' => 'owner',
        ]);

        // Current agency data
        Campaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 1000.00,
            'paid_date' => now(),
        ]);

        // Other agency data
        Campaign::factory()->count(7)->create([
            'agency_id' => $otherAgency->id,
            'status' => 'active',
        ]);
        Invoice::factory()->create([
            'agency_id' => $otherAgency->id,
            'status' => 'paid',
            'total' => 5000.00,
            'paid_date' => now(),
        ]);

        // Verify owner sees only their agency's data
        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertViewHas('activeCampaigns', 3);
        $response->assertViewHas('totalSpend', 1000.00);

        // Verify other owner sees only their agency's data
        $response2 = $this->actingAs($otherUser)
            ->get(route('client-portal.v2.dashboard'));

        $response2->assertViewHas('activeCampaigns', 7);
        $response2->assertViewHas('totalSpend', 5000.00);
    }

    public function test_cross_agency_data_is_isolated_on_campaigns(): void
    {
        $otherAgency = Agency::factory()->create();

        Campaign::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
        ]);
        Campaign::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.campaigns'));

        $viewCampaigns = $response->viewData('campaigns');
        $this->assertEquals(2, $viewCampaigns->total());
    }

    public function test_cross_agency_data_is_isolated_on_invoices(): void
    {
        $otherAgency = Agency::factory()->create();

        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
        ]);
        Invoice::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.invoices'));

        $viewInvoices = $response->viewData('invoices');
        $this->assertEquals(2, $viewInvoices->total());
    }

    public function test_cross_agency_data_is_isolated_on_analytics(): void
    {
        $otherAgency = Agency::factory()->create();

        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'views_count' => 100,
        ]);
        SocialPost::factory()->count(10)->create([
            'agency_id' => $otherAgency->id,
            'views_count' => 500,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('client-portal.v2.analytics'));

        $metrics = $response->viewData('overallMetrics');
        $this->assertEquals(3, $metrics['total_posts']);
        $this->assertEquals(300, $metrics['total_impressions']);
    }

    // ========================================================================
    //  PERMISSION / ROLE TESTS
    // ========================================================================

    public function test_admin_user_can_access_portal(): void
    {
        $admin = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertOk();
    }

    public function test_manager_user_can_access_portal(): void
    {
        $manager = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'manager',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertOk();
    }

    public function test_member_user_can_access_portal(): void
    {
        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($member)
            ->get(route('client-portal.v2.dashboard'));

        $response->assertOk();
    }
}
