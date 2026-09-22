<?php

namespace Tests\Feature\Billing;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingHealthTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create([
            'subscription_status' => 'active',
        ]);
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    /** Test 1: Billing health page loads successfully */
    public function test_billing_health_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $response->assertOk();
        $response->assertViewIs('billing.health.index');
    }

    /** Test 2: Index view receives all required variables */
    public function test_index_view_has_required_variables(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $response->assertViewHas('agency');
        $response->assertViewHas('metrics');
        $response->assertViewHas('forecast');
        $response->assertViewHas('overdueInvoices');
        $response->assertViewHas('paymentTimeline');
        $response->assertViewHas('planDistribution');
    }

    /** Test 3: Unauthenticated users are redirected */
    public function test_unauthenticated_users_redirected(): void
    {
        $response = $this->get(route('billing.health'));
        $response->assertRedirect(route('login'));
    }

    /** Test 4: MRR is calculated correctly from paid invoices */
    public function test_mrr_calculation_from_paid_invoices(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        Invoice::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'paid_date' => Carbon::now(),
            'total' => 1000,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $response->assertOk();

        $metrics = $response->viewData('metrics');
        $this->assertEquals(3000, $metrics['mrr']);
    }

    /** Test 5: Churn rate calculation */
    public function test_churn_rate_calculation(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        // Active subscriptions
        ClientSubscription::factory()->count(8)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'start_date' => Carbon::now()->subMonths(2),
        ]);

        // Cancelled this month
        ClientSubscription::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(20.0, $metrics['churn_rate']); // 2/10 * 100
        $this->assertEquals('critical', $metrics['churn_status']);
    }

    /** Test 6: Overdue invoices are detected */
    public function test_overdue_invoices_detected(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        Invoice::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(15),
            'total' => 500,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');
        $overdue = $response->viewData('overdueInvoices');

        $this->assertEquals(3, $metrics['overdue_count']);
        $this->assertEquals(1500, $metrics['overdue_amount']);
        $this->assertCount(3, $overdue);
    }

    /** Test 7: Forecast data includes labels and datasets */
    public function test_forecast_data_structure(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $forecast = $response->viewData('forecast');

        $this->assertArrayHasKey('labels', $forecast);
        $this->assertArrayHasKey('historical', $forecast);
        $this->assertArrayHasKey('projected', $forecast);
        $this->assertArrayHasKey('growth_rate', $forecast);
        $this->assertArrayHasKey('arr_projection', $forecast);
        $this->assertArrayHasKey('arr_formatted', $forecast);
        $this->assertCount(12, $forecast['labels']); // 6 historical + 6 projected
    }

    /** Test 8: Metrics endpoint returns JSON */
    public function test_metrics_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('billing.health.metrics'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'mrr',
                    'mrr_formatted',
                    'mrr_growth',
                    'mrr_growth_direction',
                    'churn_rate',
                    'churn_status',
                    'cancelled_count',
                    'overdue_count',
                    'overdue_amount',
                    'overdue_amount_formatted',
                    'collected_this_month',
                    'collected_formatted',
                    'outstanding_amount',
                    'outstanding_formatted',
                    'total_revenue',
                    'total_revenue_formatted',
                    'avg_invoice_value',
                    'avg_invoice_formatted',
                    'pending_count',
                    'pending_amount',
                    'pending_formatted',
                ],
            ]);
    }

    /** Test 9: Forecast endpoint accepts months parameter */
    public function test_forecast_endpoint_accepts_months_parameter(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('billing.health.forecast', ['months' => 12]));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(18, $data['labels']); // 6 historical + 12 projected
    }

    /** Test 10: Collected amount is calculated from paid invoices this month */
    public function test_collected_this_month_calculation(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        Invoice::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'paid_date' => Carbon::now()->startOfMonth()->addDays(5),
            'total' => 750,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(1500, $metrics['collected_this_month']);
    }

    /** Test 11: Outstanding amount includes pending and overdue */
    public function test_outstanding_amount_calculation(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'pending',
            'total' => 300,
        ]);

        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(10),
            'total' => 200,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(500, $metrics['outstanding_amount']);
        $this->assertEquals(1, $metrics['pending_count']);
    }

    /** Test 12: Average invoice value calculation */
    public function test_average_invoice_value(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'total' => 100,
        ]);
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'total' => 300,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(200, $metrics['avg_invoice_value']);
    }

    /** Test 13: Total revenue is sum of all paid invoices */
    public function test_total_revenue_lifetime(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        Invoice::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'total' => 1000,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(5000, $metrics['total_revenue']);
    }

    /** Test 14: Payment timeline shows recent payments */
    public function test_payment_timeline_content(): void
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Acme Corp',
        ]);

        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'paid_date' => Carbon::now()->subDays(1),
            'total' => 2500,
            'payment_method' => 'stripe',
            'invoice_number' => 'INV-2026-0001',
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $timeline = $response->viewData('paymentTimeline');

        $this->assertCount(1, $timeline);
        $this->assertEquals('Acme Corp', $timeline[0]['client_name']);
        $this->assertEquals('$2,500.00', $timeline[0]['amount_formatted']);
    }

    /** Test 15: Plan distribution returns subscription data */
    public function test_plan_distribution_data(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        ClientSubscription::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'plan_name' => 'professional',
            'price' => 100,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $distribution = $response->viewData('planDistribution');

        $this->assertCount(1, $distribution);
        $this->assertEquals('professional', $distribution[0]['plan']);
        $this->assertEquals(3, $distribution[0]['count']);
    }

    /** Test 16: Churn rate with no cancellations is zero */
    public function test_churn_rate_zero_when_no_cancellations(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        ClientSubscription::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'start_date' => Carbon::now()->subMonths(2),
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(0, $metrics['churn_rate']);
        $this->assertEquals('excellent', $metrics['churn_status']);
    }

    /** Test 17: MRR growth direction indicator */
    public function test_mrr_growth_direction(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        // Last month paid
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'paid_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(5),
            'total' => 500,
        ]);

        // This month paid (higher)
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'paid_date' => Carbon::now(),
            'total' => 1000,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals('up', $metrics['mrr_growth_direction']);
        $this->assertGreaterThan(0, $metrics['mrr_growth']);
    }

    /** Test 18: Overdue invoices severity classification */
    public function test_overdue_severity_classification(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        // Critical (>30 days)
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(45),
        ]);

        // Warning (14-30 days)
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(20),
        ]);

        // Info (<14 days)
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $overdue = $response->viewData('overdueInvoices');

        $severities = array_column($overdue, 'severity');
        $this->assertContains('critical', $severities);
        $this->assertContains('warning', $severities);
        $this->assertContains('info', $severities);
    }

    /** Test 19: Empty state - no invoices at all */
    public function test_empty_state_no_invoices(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals(0, $metrics['mrr']);
        $this->assertEquals(0, $metrics['overdue_count']);
        $this->assertEquals(0, $metrics['pending_count']);
        $this->assertEquals('$0.00', $metrics['collected_formatted']);
    }

    /** Test 20: Subscription MRR adds to invoice MRR */
    public function test_subscription_mrr_included(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        // Invoice MRR
        Invoice::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'status' => 'paid',
            'paid_date' => Carbon::now(),
            'total' => 500,
        ]);

        // Subscription MRR
        ClientSubscription::factory()->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'price' => 200,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        // Total MRR = invoice MRR + subscription MRR
        $this->assertEquals(700, $metrics['mrr']);
    }

    /** Test 21: Other agency's data is not included */
    public function test_other_agency_data_isolated(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherClient = Client::factory()->create(['agency_id' => $otherAgency->id]);

        Invoice::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
            'client_id' => $otherClient->id,
            'status' => 'paid',
            'total' => 9999,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        // Should NOT include other agency's revenue
        $this->assertEquals(0, $metrics['mrr']);
        $this->assertEquals(0, $metrics['total_revenue']);
    }

    /** Test 22: Churn status healthy at low rates */
    public function test_churn_status_healthy_at_low_rate(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        ClientSubscription::factory()->count(48)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
            'start_date' => Carbon::now()->subMonths(2),
        ]);

        ClientSubscription::factory()->count(1)->create([
            'agency_id' => $this->agency->id,
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $metrics = $response->viewData('metrics');

        $this->assertEquals('healthy', $metrics['churn_status']);
        $this->assertLessThanOrEqual(5, $metrics['churn_rate']);
    }

    /** Test 23: ARR projection included in forecast */
    public function test_arr_projection_in_forecast(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $forecast = $response->viewData('forecast');

        $this->assertArrayHasKey('arr_projection', $forecast);
        $this->assertArrayHasKey('arr_formatted', $forecast);
        $this->assertIsString($forecast['arr_formatted']);
        $this->assertStringStartsWith('$', $forecast['arr_formatted']);
    }

    /** Test 24: Forecast growth rate is numeric */
    public function test_forecast_growth_rate_is_numeric(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        // Create some historical data
        for ($i = 3; $i >= 1; $i--) {
            Invoice::factory()->create([
                'agency_id' => $this->agency->id,
                'client_id' => $client->id,
                'status' => 'paid',
                'paid_date' => Carbon::now()->subMonths($i)->startOfMonth(),
                'total' => 1000 * $i,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('billing.health'));
        $forecast = $response->viewData('forecast');

        $this->assertIsNumeric($forecast['growth_rate']);
    }
}
