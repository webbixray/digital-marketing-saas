<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiInvoiceTest extends TestCase
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

        $response = $this->actingAs($this->user)->getJson('/api/v1/invoices');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_it_creates_an_invoice(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/invoices', [
            'total' => 100.00,
            'notes' => 'Test invoice',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('invoices', ['total' => 100.00]);
    }

    public function test_it_validates_invoice_creation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/invoices', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total']);
    }

    public function test_it_shows_an_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $invoice->id);
    }

    public function test_it_prevents_showing_other_agency_invoices(): void
    {
        $otherAgency = Agency::factory()->create();
        $invoice = Invoice::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertNotFound();
    }

    public function test_it_updates_an_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/invoices/{$invoice->id}", [
            'total' => 200.00,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'total' => 200.00]);
    }

    public function test_it_deletes_an_invoice(): void
    {
        $invoice = Invoice::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertNoContent();
        // Invoices use soft deletes (financial audit trail).
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/invoices');

        $response->assertUnauthorized();
    }
}
