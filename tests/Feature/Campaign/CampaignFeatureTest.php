<?php

namespace Tests\Feature\Campaign;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignFeatureTest extends TestCase
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

    public function test_it_lists_campaigns(): void
    {
        Campaign::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('campaigns.index'));
        $response->assertStatus(200);
    }

    public function test_it_creates_campaign(): void
    {
        $response = $this->actingAs($this->user)->post(route('campaigns.store'), [
            'name' => 'Test Campaign',
            'type' => 'general',
            'description' => 'Test description',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', ['name' => 'Test Campaign']);
    }

    public function test_it_validates_campaign_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('campaigns.store'), []);
        $response->assertSessionHasErrors();
    }

    public function test_it_shows_campaign(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('campaigns.show', $campaign));
        $response->assertStatus(200);
    }

    public function test_it_updates_campaign(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('campaigns.update', $campaign), [
            'name' => 'Updated Campaign',
        ]);
        $response->assertRedirect();
    }

    public function test_it_deletes_campaign(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('campaigns.destroy', $campaign));
        $response->assertRedirect();
        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_it_prevents_unauthorized_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $campaign = Campaign::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('campaigns.show', $campaign));
        $response->assertForbidden();
    }

    public function test_it_filters_by_status(): void
    {
        Campaign::factory()->create(['agency_id' => $this->agency->id, 'status' => 'active']);
        Campaign::factory()->create(['agency_id' => $this->agency->id, 'status' => 'completed']);
        $response = $this->actingAs($this->user)->get(route('campaigns.index', ['status' => 'active']));
        $response->assertStatus(200);
    }
}
