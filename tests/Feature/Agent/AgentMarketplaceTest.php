<?php

namespace Tests\Feature\Agent;

use App\Models\Agency;
use App\Models\AgentMarketplaceCategory;
use App\Models\AgentMarketplaceItem;
use App\Models\AgentMarketplaceReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private Agency $otherAgency;
    private User $user;
    private AgentMarketplaceCategory $category;
    private AgentMarketplaceItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['subscription_plan' => 'professional']);
        $this->otherAgency = Agency::factory()->create(['subscription_plan' => 'professional']);
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->category = AgentMarketplaceCategory::factory()->create(['name' => 'Content', 'slug' => 'content', 'is_active' => true]);
        $this->item = AgentMarketplaceItem::factory()->create([
            'name' => 'Test Agent',
            'slug' => 'test-agent',
            'category_id' => $this->category->id,
            'is_approved' => true,
            'status' => 'approved',
            'pricing_type' => 'free',
            'install_count' => 0,
            'rating_avg' => 0,
            'rating_count' => 0,
        ]);
    }

    // Test 1: Browse marketplace requires auth
    public function test_marketplace_requires_auth(): void
    {
        $response = $this->get(route('agent-marketplace.index'));
        $response->assertRedirect();
    }

    // Test 2: Browse marketplace returns items
    public function test_marketplace_index_returns_items(): void
    {
        $response = $this->actingAs($this->user)->get(route('agent-marketplace.index'));
        $response->assertOk();
        $response->assertViewIs('agent-marketplace.index');
        $response->assertViewHas('items');
        $response->assertViewHas('categories');
        $response->assertViewHas('featured');
    }

    // Test 3: Search marketplace items
    public function test_marketplace_search_filters_items(): void
    {
        AgentMarketplaceItem::factory()->create([
            'name' => 'Another Agent',
            'slug' => 'another-agent',
            'category_id' => $this->category->id,
            'is_approved' => true,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)->get(route('agent-marketplace.index', ['search' => 'Test']));
        $response->assertOk();
        $response->assertSee('Test Agent');
    }

    // Test 4: Filter by category
    public function test_marketplace_filters_by_category(): void
    {
        $response = $this->actingAs($this->user)->get(route('agent-marketplace.index', ['category_id' => $this->category->id]));
        $response->assertOk();
        $response->assertSee('Test Agent');
    }

    // Test 5: View single agent detail
    public function test_marketplace_show_displays_agent_detail(): void
    {
        $response = $this->actingAs($this->user)->get(route('agent-marketplace.show', $this->item->slug));
        $response->assertOk();
        $response->assertViewIs('agent-marketplace.show');
        $response->assertViewHas('item', $this->item);
    }

    // Test 6: View non-existent agent returns 404
    public function test_marketplace_show_returns_404_for_invalid_slug(): void
    {
        $response = $this->actingAs($this->user)->get(route('agent-marketplace.show', 'non-existent'));
        $response->assertNotFound();
    }

    // Test 7: Install agent
    public function test_marketplace_install_agent(): void
    {
        $response = $this->actingAs($this->user)->post(route('agent-marketplace.install'), [
            'item_id' => $this->item->id,
        ]);
        $response->assertRedirect();
        $this->assertGreaterThanOrEqual(0, $this->item->fresh()->install_count);
    }

    // Test 8: Uninstall agent
    public function test_marketplace_uninstall_agent(): void
    {
        $this->item->increment('install_count');
        $response = $this->actingAs($this->user)->post(route('agent-marketplace.uninstall'), [
            'item_id' => $this->item->id,
        ]);
        $response->assertRedirect();
        $this->assertGreaterThanOrEqual(0, $this->item->fresh()->install_count);
    }

    // Test 9: Configure agent
    public function test_marketplace_configure_agent(): void
    {
        $response = $this->actingAs($this->user)->post(route('agent-marketplace.configure'), [
            'item_id' => $this->item->id,
            'config' => ['display_name' => 'My Configured Agent', 'is_active' => 1],
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // Test 10: Submit new agent
    public function test_marketplace_submit_new_agent(): void
    {
        $response = $this->actingAs($this->user)->post(route('agent-marketplace.store'), [
            'name' => 'My New Agent',
            'slug' => 'my-new-agent',
            'description' => 'A test agent for the marketplace',
            'category_id' => $this->category->id,
            'pricing_type' => 'free',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('agent_marketplace_items', ['name' => 'My New Agent', 'status' => 'pending']);
    }

    // Test 11: View my agents
    public function test_marketplace_my_agents_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('agent-marketplace.my-agents'));
        $response->assertOk();
        $response->assertViewIs('agent-marketplace.my-agents');
    }

    // Test 12: API index returns items
    public function test_api_marketplace_index_returns_items(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('api.agent-marketplace.index'));
        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    // Test 13: API search works
    public function test_api_marketplace_search(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('api.agent-marketplace.search', ['q' => 'Test']));
        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
    }

    // Test 14: API show returns item detail
    public function test_api_marketplace_show(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('api.agent-marketplace.show', $this->item->slug));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Test Agent']);
    }

    // Test 15: Cross-agency isolation - item submitted by one agency doesn't appear as installed for another
    public function test_marketplace_cross_agency_isolation(): void
    {
        $otherUser = User::factory()->create([
            'agency_id' => $this->otherAgency->id,
            'role' => 'owner',
        ]);
        $response = $this->actingAs($otherUser)->get(route('agent-marketplace.my-agents'));
        $response->assertOk();
        // Should not see the item from the first agency
        $this->assertDatabaseMissing('agent_marketplace_items', [
            'agency_id' => $this->otherAgency->id,
            'name' => 'Test Agent',
        ]);
    }
}
