<?php

namespace Tests\Feature\Billing;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    /** Test billing index shows current plan and usage. */
    public function test_billing_index_shows_current_plan_and_usage(): void
    {
        Invoice::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('agency.billing'));

        $response->assertOk();
        $response->assertViewIs('billing.index');
        $response->assertViewHas('plans');
        $response->assertViewHas('currentPlan');
        $response->assertViewHas('invoices');
    }

    /** Test upgrade page shows plan comparison. */
    public function test_upgrade_page_shows_plan_comparison(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.upgrade'));

        $response->assertOk();
        $response->assertViewIs('billing.upgrade');
        $response->assertViewHas('plans');
        $response->assertViewHas('currentPlan');
        $response->assertSee('Starter');
        $response->assertSee('Pro');
        $response->assertSee('Enterprise');
    }

    /** Test checkout redirects free plan users with error. */
    public function test_checkout_rejects_free_plan(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.checkout', 'free'));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Free plan does not require checkout.');
    }

    /** Test checkout validates invalid plan. */
    public function test_checkout_rejects_invalid_plan(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.checkout', 'invalid_plan'));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid plan selected.');
    }

    /** Test invoices page lists agency invoices. */
    public function test_invoices_page_lists_agency_invoices(): void
    {
        Invoice::factory()->count(5)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('agency.invoices'));

        $response->assertOk();
        $response->assertViewIs('billing.invoices');
        $response->assertViewHas('invoices');
    }

    /** Test success page loads correctly. */
    public function test_success_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.success'));

        $response->assertOk();
        $response->assertViewIs('billing.success');
    }

    /** Test cancel page redirects with warning. */
    public function test_cancel_page_redirects_with_warning(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.cancel'));

        $response->assertRedirect(route('agency.billing'));
        $response->assertSessionHas('warning', 'Checkout was canceled.');
    }

    /** Test invoice download enforces agency ownership. */
    public function test_invoice_download_requires_ownership(): void
    {
        $otherAgency = Agency::factory()->create();
        $invoice = Invoice::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('billing.invoice.download', $invoice));

        $response->assertForbidden();
    }

    /** Test invoices page shows only agency invoices. */
    public function test_invoices_page_shows_only_agency_invoices(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        Invoice::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
        ]);

        $otherAgency = Agency::factory()->create();
        Invoice::factory()->count(2)->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('agency.invoices'));

        $response->assertOk();
        $invoices = $response->viewData('invoices');
        $this->assertCount(3, $invoices);
    }

    /** Test billing webhook rejects non-JSON content type. */
    public function test_webhook_rejects_non_json(): void
    {
        $response = $this->post(route('billing.webhook'), [], [
            'Content-Type' => 'text/plain',
        ]);

        $response->assertStatus(415);
        $response->assertJson(['error' => 'Invalid content type.']);
    }

    /** Test billing webhook rejects empty payload. */
    public function test_webhook_rejects_empty_payload(): void
    {
        $response = $this->call('POST', route('billing.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '');

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Empty payload.']);
    }

    /** Test billing webhook rejects missing signature. */
    public function test_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson(route('billing.webhook'), [
            'type' => 'payment_intent.succeeded',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing Stripe-Signature header.']);
    }

    /** Test billing requires authentication. */
    public function test_billing_requires_authentication(): void
    {
        $response = $this->get(route('agency.billing'));

        $response->assertRedirect(route('login'));
    }

    /** Test invoices page requires authentication. */
    public function test_invoices_requires_authentication(): void
    {
        $response = $this->get(route('agency.invoices'));

        $response->assertRedirect(route('login'));
    }
}
