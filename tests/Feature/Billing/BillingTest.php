<?php

namespace Tests\Feature\Billing;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
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

    public function test_invoices_page_lists_agency_invoices(): void
    {
        Invoice::factory()->count(5)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('agency.invoices'));

        $response->assertOk();
        $response->assertViewIs('billing.invoices');
        $response->assertViewHas('invoices');
    }
}
