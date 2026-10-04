<?php

namespace Tests\Feature\Invoice;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
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

    public function test_it_lists_invoices(): void
    {
        Invoice::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('invoices.index'));
        $response->assertStatus(200);
    }

    public function test_it_creates_invoice(): void
    {
        $response = $this->actingAs($this->user)->post(route('invoices.store'), [
            'client_name' => 'Test Client',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [['description' => 'Service', 'amount' => 100]],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['client_name' => 'Test Client']);
    }

    public function test_it_shows_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $response->assertStatus(200);
    }

    public function test_it_updates_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('invoices.update', $invoice), [
            'client_name' => 'Updated Client',
        ]);
        $response->assertRedirect();
    }

    public function test_it_deletes_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('invoices.destroy', $invoice));
        $response->assertRedirect();
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_it_marks_invoice_paid(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id, 'status' => 'pending']);
        $response = $this->actingAs($this->user)->post(route('invoices.paid', $invoice));
        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_it_prevents_unauthorized_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $invoice = Invoice::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('invoices.show', $invoice));
        $response->assertForbidden();
    }

    public function test_it_filters_by_status(): void
    {
        Invoice::factory()->create(['agency_id' => $this->agency->id, 'status' => 'pending']);
        Invoice::factory()->create(['agency_id' => $this->agency->id, 'status' => 'paid']);
        $response = $this->actingAs($this->user)->get(route('invoices.index', ['status' => 'pending']));
        $response->assertStatus(200);
    }
}
