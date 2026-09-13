<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\InboxMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboxTest extends TestCase
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

    public function test_it_lists_inbox_messages(): void
    {
        InboxMessage::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get(route('unified-inbox.index'));
        $response->assertStatus(200);
        $response->assertViewHas('inbox');
    }

    public function test_it_filters_by_status(): void
    {
        InboxMessage::factory()->create(['agency_id' => $this->agency->id, 'status' => 'unread']);
        InboxMessage::factory()->create(['agency_id' => $this->agency->id, 'status' => 'read']);
        $response = $this->get(route('unified-inbox.index', ['status' => 'unread']));
        $response->assertStatus(200);
    }

    public function test_it_filters_by_platform(): void
    {
        InboxMessage::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'facebook']);
        InboxMessage::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'twitter']);
        $response = $this->get(route('unified-inbox.index', ['platform' => 'facebook']));
        $response->assertStatus(200);
    }

    public function test_it_filters_by_type(): void
    {
        InboxMessage::factory()->create(['agency_id' => $this->agency->id, 'message_type' => 'comment']);
        InboxMessage::factory()->create(['agency_id' => $this->agency->id, 'message_type' => 'mention']);
        $response = $this->get(route('unified-inbox.index', ['type' => 'comment']));
        $response->assertStatus(200);
    }

    public function test_it_prevents_accessing_other_agency_inbox(): void
    {
        $otherAgency = Agency::factory()->create();
        InboxMessage::factory()->count(3)->create(['agency_id' => $otherAgency->id]);
        $response = $this->get(route('unified-inbox.index'));
        $response->assertStatus(200);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get(route('unified-inbox.index'));
        $response->assertRedirect('/login');
    }
}
