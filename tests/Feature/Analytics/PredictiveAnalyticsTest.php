<?php

namespace Tests\Feature\Analytics;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Invoice;
use App\Models\SocialListening;
use App\Models\SocialPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PredictiveAnalyticsTest extends TestCase
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
    public function test_it_shows_predictive_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('predictive.dashboard'));

        $response->assertOk();
        $response->assertViewIs('predictive-analytics.dashboard');
        $response->assertViewHas('churn');
        $response->assertViewHas('revenue');
        $response->assertViewHas('optimal_times');
        $response->assertViewHas('trends');
    }

    /** @test */
    public function test_it_predicts_churn_for_client()
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'last_contact_at' => now()->subDays(120),
        ]);

        // No posts in last 60 days — triggers low engagement risk
        // No active subscription — triggers critical risk

        $service = app(\App\Services\Analytics\Predictive\ChurnPredictionService::class);
        $prediction = $service->predictChurn($client->id, $this->agency->id);

        $this->assertArrayHasKey('risk_score', $prediction);
        $this->assertArrayHasKey('risk_level', $prediction);
        $this->assertArrayHasKey('risk_factors', $prediction);
        $this->assertArrayHasKey('recommendation', $prediction);
        $this->assertGreaterThan(0, $prediction['risk_score']);
        $this->assertNotEmpty($prediction['risk_factors']);
    }

    /** @test */
    public function test_it_shows_churn_risk_factors()
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'last_contact_at' => now()->subDays(120),
        ]);

        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(30),
        ]);

        $service = app(\App\Services\Analytics\Predictive\ChurnPredictionService::class);
        $factors = $service->getChurnRiskFactors($client->id);

        $this->assertNotEmpty($factors);
        $factorNames = array_column($factors, 'factor');
        $this->assertContains('low_engagement', $factorNames);
        $this->assertContains('payment_overdue', $factorNames);
    }

    /** @test */
    public function test_it_returns_churn_risk_score()
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);

        $service = app(\App\Services\Analytics\Predictive\ChurnPredictionService::class);
        $score = $service->getChurnRiskScore($client->id);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(1, $score);
    }

    /** @test */
    public function test_it_identifies_high_risk_clients()
    {
        $highRiskClient = Client::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'last_contact_at' => now()->subDays(120),
        ]);

        $safeClient = Client::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        ClientSubscription::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $safeClient->id,
            'status' => 'active',
            'plan_name' => 'professional',
            'price' => 199,
        ]);
        SocialPost::factory()->count(10)->create([
            'client_id' => $safeClient->id,
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $service = app(\App\Services\Analytics\Predictive\ChurnPredictionService::class);
        $highRisk = $service->getHighRiskClients($this->agency->id, 0.5);

        $this->assertGreaterThanOrEqual(1, $highRisk->count());
        $this->assertTrue($highRisk->contains(fn ($c) => $c->id === $highRiskClient->id));
    }

    /** @test */
    public function test_it_calculates_revenue_forecast()
    {
        ClientSubscription::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'plan_name' => 'professional',
            'price' => 199,
        ]);

        Invoice::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'status' => 'paid',
            'total' => 199,
            'paid_date' => Carbon::now()->subDays(10),
        ]);

        $service = app(\App\Services\Analytics\Predictive\RevenueForecastService::class);
        $forecast = $service->forecastRevenue($this->agency->id, 3);

        $this->assertArrayHasKey('current_mrr', $forecast);
        $this->assertArrayHasKey('forecast', $forecast);
        $this->assertCount(3, $forecast['forecast']);
        $this->assertEquals(597, $forecast['current_mrr']); // 3 * 199
    }

    /** @test */
    public function test_it_forecasts_mrr_and_arr()
    {
        ClientSubscription::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'plan_name' => 'professional',
            'price' => 199,
        ]);

        $service = app(\App\Services\Analytics\Predictive\RevenueForecastService::class);
        $mrr = $service->forecastMRR($this->agency->id);
        $arr = $service->forecastARR($this->agency->id);

        $this->assertArrayHasKey('current_mrr', $mrr);
        $this->assertCount(12, $mrr['next_12_months']);
        $this->assertEquals(199, $mrr['current_mrr']);
        $this->assertEquals(2388, $arr['current_arr']); // 199 * 12
    }

    /** @test */
    public function test_it_gets_plan_distribution()
    {
        ClientSubscription::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'plan_name' => 'professional',
            'price' => 199,
        ]);
        ClientSubscription::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'plan_name' => 'enterprise',
            'price' => 499,
        ]);

        $service = app(\App\Services\Analytics\Predictive\RevenueForecastService::class);
        $dist = $service->getPlanDistribution($this->agency->id);

        $this->assertEquals(3, $dist['total_clients']);
        $this->assertCount(2, $dist['distribution']);
        $this->assertEquals(897, $dist['total_revenue']); // 2*199 + 499
    }

    /** @test */
    public function test_it_detects_optimal_posting_times()
    {
        SocialPost::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(5)->setHour(14),
            'engagement_rate' => 4.5,
        ]);
        SocialPost::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(3)->setHour(10),
            'engagement_rate' => 2.0,
        ]);

        $service = app(\App\Services\Analytics\Predictive\OptimalTimeService::class);
        $bestTimes = $service->getBestPostingTimes($this->agency->id, 'facebook');

        $this->assertArrayHasKey('best_times', $bestTimes);
        $this->assertNotEmpty($bestTimes['best_times']);
        // The 14:00 slot should have higher engagement
        $this->assertEquals(14, $bestTimes['top_hour']);
    }

    /** @test */
    public function test_it_generates_engagement_heatmap()
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(5)->setHour(16),
            'engagement_rate' => 3.5,
        ]);

        $service = app(\App\Services\Analytics\Predictive\OptimalTimeService::class);
        $heatmap = $service->getEngagementHeatmap($this->agency->id, 'instagram');

        $this->assertArrayHasKey('heatmap', $heatmap);
        $this->assertArrayHasKey('peak_day', $heatmap);
        $this->assertArrayHasKey('peak_hour', $heatmap);
        $this->assertEquals(16, $heatmap['peak_hour']);
    }

    /** @test */
    public function test_it_detects_emerging_trends()
    {
        // Current period posts with a topic
        SocialPost::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(5),
            'tags' => ['ai-tools', 'marketing'],
            'hashtags' => ['aitools', 'marketing'],
            'engagement_rate' => 3.0,
        ]);

        // Previous period with same topic but less frequency
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(20),
            'tags' => ['ai-tools'],
            'hashtags' => ['aitools'],
            'engagement_rate' => 2.0,
        ]);

        $service = app(\App\Services\Analytics\Predictive\TrendDetectionService::class);
        $trends = $service->detectTrends($this->agency->id, 30);

        $this->assertArrayHasKey('trending', $trends);
        $this->assertArrayHasKey('overall_velocity', $trends);
    }

    /** @test */
    public function test_it_shows_hashtag_trends()
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(5),
            'hashtags' => ['trending', 'viral'],
        ]);
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => Carbon::now()->subDays(20),
            'hashtags' => ['trending'],
        ]);

        $service = app(\App\Services\Analytics\Predictive\TrendDetectionService::class);
        $hashtags = $service->getHashtagTrends($this->agency->id);

        $this->assertNotEmpty($hashtags);
        $this->assertArrayHasKey('hashtag', $hashtags[0]);
        $this->assertArrayHasKey('growth_rate', $hashtags[0]);
    }

    /** @test */
    public function test_it_requires_auth_for_predictive_routes()
    {
        $routes = [
            'predictive.dashboard',
            'predictive.churn',
            'predictive.revenue',
            'predictive.trends',
            'predictive.optimal-times',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect(route('login'));
        }
    }

    /** @test */
    public function test_it_prevents_cross_agency_data_access()
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        $otherClient = Client::factory()->create([
            'agency_id' => $otherAgency->id,
            'status' => 'active',
            'last_contact_at' => now()->subDays(120),
        ]);

        // Our user should not see the other agency's client data
        $response = $this->actingAs($this->user)->get(route('predictive.churn'));

        $response->assertOk();
        $predictions = $response->viewData('predictions');
        $clientIds = $predictions->pluck('client_id')->toArray();
        $this->assertNotContains($otherClient->id, $clientIds);
    }

    /** @test */
    public function test_it_shows_churn_page()
    {
        $response = $this->actingAs($this->user)->get(route('predictive.churn'));

        $response->assertOk();
        $response->assertViewIs('predictive-analytics.churn');
        $response->assertViewHas('predictions');
        $response->assertViewHas('trends');
        $response->assertViewHas('threshold');
    }

    /** @test */
    public function test_it_shows_revenue_page()
    {
        $response = $this->actingAs($this->user)->get(route('predictive.revenue'));

        $response->assertOk();
        $response->assertViewIs('predictive-analytics.revenue');
        $response->assertViewHas('forecast');
        $response->assertViewHas('mrr');
        $response->assertViewHas('arr');
        $response->assertViewHas('plan_distribution');
    }

    /** @test */
    public function test_it_shows_trends_page()
    {
        $response = $this->actingAs($this->user)->get(route('predictive.trends'));

        $response->assertOk();
        $response->assertViewIs('predictive-analytics.trends');
        $response->assertViewHas('detected');
        $response->assertViewHas('topics');
        $response->assertViewHas('hashtags');
    }

    /** @test */
    public function test_it_shows_optimal_times_page()
    {
        $response = $this->actingAs($this->user)->get(route('predictive.optimal-times'));

        $response->assertOk();
        $response->assertViewIs('predictive-analytics.optimal-times');
        $response->assertViewHas('heatmap');
        $response->assertViewHas('audience_activity');
    }

    /** @test */
    public function test_it_shows_churn_trends()
    {
        // Create subscriptions cancelled in different months
        ClientSubscription::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now()->subMonths(3)->startOfMonth(),
        ]);
        ClientSubscription::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now()->subMonths(2)->startOfMonth(),
        ]);

        $service = app(\App\Services\Analytics\Predictive\ChurnPredictionService::class);
        $trends = $service->getChurnTrends($this->agency->id, 6);

        $this->assertCount(6, $trends);
        $this->assertArrayHasKey('month', $trends[0]);
        $this->assertArrayHasKey('churn_rate', $trends[0]);
    }
}
