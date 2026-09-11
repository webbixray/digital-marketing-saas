<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_tickets(): void
    {
        SupportTicket::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/support');
        $response->assertStatus(200);
        $response->assertViewIs('support.index');
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/support/create');
        $response->assertStatus(200);
        $response->assertViewIs('support.create');
    }

    public function test_it_creates_ticket(): void
    {
        $data = [
            'subject' => 'Test Ticket',
            'description' => 'Test description',
            'priority' => 'medium',
            'category' => 'technical',
        ];
        $response = $this->post('/support', $data);
        $response->assertStatus(302);
        $response->assertRedirect('/support');
        $this->assertDatabaseHas('support_tickets', ['subject' => 'Test Ticket', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_support_ticket_creation(): void
    {
        $response = $this->post('/support', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['subject', 'description', 'priority', 'category']);
    }

    public function test_it_prevents_accessing_other_agency_tickets(): void
    {
        $otherAgency = Agency::factory()->create();
        $ticket = SupportTicket::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->get("/support/{$ticket->id}");
        $response->assertStatus(403);
    }

    public function test_it_deletes_support_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->delete("/support/{$ticket->id}");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('support_tickets', ['id' => $ticket->id]);
    }
}
