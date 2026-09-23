<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Creation & Fillable
    // =========================================================================

    public function test_client_can_be_created_with_factory(): void
    {
        $client = Client::factory()->create();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => $client->name,
        ]);
    }

    public function test_client_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();

        $client = Client::create([
            'agency_id' => $agency->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Corp',
            'industry' => 'Technology',
            'notes' => 'Important client',
            'status' => 'active',
            'last_contact_at' => now(),
            'posts_count' => 10,
            'campaigns_count' => 2,
        ]);

        $this->assertNotNull($client);
        $this->assertEquals('John Doe', $client->name);
        $this->assertEquals('john@example.com', $client->email);
        $this->assertEquals('active', $client->status);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function test_client_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $client->agency);
        $this->assertEquals($agency->id, $client->agency->id);
    }

    public function test_client_has_many_campaigns(): void
    {
        $client = Client::factory()->create();
        Campaign::factory()->count(3)->create(['client_id' => $client->id]);

        $this->assertCount(3, $client->campaigns);
        $this->assertInstanceOf(Campaign::class, $client->campaigns->first());
    }

    public function test_client_has_many_subscriptions(): void
    {
        $client = Client::factory()->create();
        ClientSubscription::factory()->count(2)->create(['client_id' => $client->id]);

        $this->assertCount(2, $client->subscriptions);
        $this->assertInstanceOf(ClientSubscription::class, $client->subscriptions->first());
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_last_contact_at_casts_to_datetime(): void
    {
        $client = Client::factory()->create([
            'last_contact_at' => '2024-01-15 10:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $client->last_contact_at);
    }

    public function test_posts_count_casts_to_integer(): void
    {
        $client = Client::factory()->create(['posts_count' => '25']);

        $this->assertIsInt($client->posts_count);
        $this->assertEquals(25, $client->posts_count);
    }

    public function test_campaigns_count_casts_to_integer(): void
    {
        $client = Client::factory()->create(['campaigns_count' => '5']);

        $this->assertIsInt($client->campaigns_count);
        $this->assertEquals(5, $client->campaigns_count);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function test_scope_active_returns_only_active(): void
    {
        Client::factory()->create(['status' => 'active']);
        Client::factory()->create(['status' => 'inactive']);
        Client::factory()->create(['status' => 'lead']);

        $results = Client::active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
    }

    public function test_scope_lead_returns_only_leads(): void
    {
        Client::factory()->create(['status' => 'lead']);
        Client::factory()->create(['status' => 'active']);
        Client::factory()->create(['status' => 'lead']);

        $results = Client::lead()->get();

        $this->assertCount(2, $results);
        $results->each(function ($client) {
            $this->assertEquals('lead', $client->status);
        });
    }

    public function test_scope_for_agency_from_trait_filters_correctly(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        Client::factory()->create(['agency_id' => $agency1->id]);
        Client::factory()->create(['agency_id' => $agency2->id]);

        $results = Client::forAgency($agency1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($agency1->id, $results->first()->agency_id);
    }

    // =========================================================================
    // Factory States
    // =========================================================================

    public function test_factory_active_state_sets_active_status(): void
    {
        $client = Client::factory()->active()->create();

        $this->assertEquals('active', $client->status);
    }

    public function test_factory_lead_state_sets_lead_status(): void
    {
        $client = Client::factory()->lead()->create();

        $this->assertEquals('lead', $client->status);
    }

    // =========================================================================
    // Soft Deletes
    // =========================================================================

    public function test_client_uses_soft_deletes(): void
    {
        $client = Client::factory()->create();
        $clientId = $client->id;

        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $clientId]);
    }

    // =========================================================================
    // HasAgency Trait
    // =========================================================================

    public function test_client_uses_has_agency_trait(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $this->assertEquals($agency->id, $client->agency_id);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'agency_id' => $agency->id,
        ]);
    }
}
