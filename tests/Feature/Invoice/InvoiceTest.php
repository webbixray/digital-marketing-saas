<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    public function test_it_lists_invoices(): void
    {
        Invoice::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/invoices');
        $response->assertStatus(200);
        $response->assertViewHas('invoices');
        $response->assertViewHas('stats');
    }

    public function test_it_filters_by_status(): void
    {
        Invoice::factory()->create(['agency_id' => $this->agency->id, 'status' => 'pending']);
        Invoice::factory()->create(['agency_id' => $this->agency->id, 'status' => 'paid']);
        $response = $this->get('/invoices?status=pending');
        $response->assertStatus(200);
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/invoices/create');
        $response->assertStatus(200);
    }

    public function test_it_creates_invoice(): void
    {
        $data = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => 'Test invoice',
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100],
            ],
        ];
        $response = $this->post('/invoices', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', ['agency_id' => $this->agency->id, 'total' => 100]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->post('/invoices', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['issue_date', 'due_date', 'items']);
    }

    public function test_it_prevents_accessing_other_agency_invoices(): void
    {
        $otherAgency = Agency::factory()->create();
        Invoice::factory()->count(3)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get('/invoices');
        $response->assertStatus(200);
        $invoices = $response->viewData('invoices');
        $this->assertCount(0, $invoices);
    }

    public function test_it_shows_single_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->get("/invoices/{$invoice->id}");
        $response->assertStatus(200);
    }

    public function test_it_marks_invoice_paid(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id, 'status' => 'pending']);
        $response = $this->post("/invoices/{$invoice->id}/paid", [
            'payment_method' => 'manual',
            'transaction_id' => 'txn_123',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/invoices');
        $response->assertRedirect('/login');
    }
}
