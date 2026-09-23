<?php

namespace Tests\Feature\Campaign;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
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

    public function test_index_shows_campaigns(): void
    {
        Campaign::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('campaigns.index'));
        $response->assertOk();
        $response->assertViewIs('campaigns.index');
        $response->assertViewHas('campaigns');
    }

    public function test_create_campaign(): void
    {
        $response = $this->actingAs($this->user)->post(route('campaigns.store'), [
            'name' => 'Test Campaign',
            'type' => 'general',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_update_campaign(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('campaigns.update', $campaign), [
            'name' => 'Updated Campaign',
            'type' => 'general',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'name' => 'Updated Campaign']);
    }

    public function test_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('campaigns.destroy', $campaign));
        $response->assertRedirect(route('campaigns.index'));
        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_campaign_status_change(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        $response = $this->actingAs($this->user)->post(route('campaigns.status', $campaign), [
            'status' => 'active',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => 'active']);
    }

    public function test_campaign_scheduling(): void
    {
        $response = $this->actingAs($this->user)->post(route('campaigns.store'), [
            'name' => 'Scheduled Campaign',
            'type' => 'seasonal',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Scheduled Campaign',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_requires_auth(): void
    {
        $response = $this->get(route('campaigns.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_validation_errors(): void
    {
        $response = $this->actingAs($this->user)->post(route('campaigns.store'), []);
        $response->assertSessionHasErrors(['name', 'type']);
    }

    public function test_cross_agency_scoping(): void
    {
        $otherAgency = Agency::factory()->create();
        $campaign = Campaign::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('campaigns.show', $campaign));
        $response->assertForbidden();
    }
}
