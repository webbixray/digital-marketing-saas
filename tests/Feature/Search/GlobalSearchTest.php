<?php

namespace Tests\Feature\Search;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ContentAsset;
use App\Models\SearchHistory;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
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

    public function test_search_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('search.index'));

        $response->assertOk();
        $response->assertViewIs('search.index');
    }

    public function test_search_all_types(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'content' => 'Searchable post content',
            'platform' => 'twitter',
        ]);

        Campaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Test Campaign',
        ]);

        Client::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Test Client',
        ]);

        $response = $this->actingAs($this->user)->get(route('search.index', [
            'q' => 'Test',
            'type' => 'all',
        ]));

        $response->assertOk();
        $response->assertViewHas('results');
    }

    public function test_search_posts(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'content' => 'Find this post',
            'platform' => 'twitter',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('search.store'), [
            'query' => 'Find',
            'type' => 'posts',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'results', 'total_count']);
        $response->assertJsonFragment(['success' => true]);
    }

    public function test_search_campaigns(): void
    {
        Campaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Summer Sale Campaign',
        ]);

        $response = $this->actingAs($this->user)->get(route('search.index', [
            'q' => 'Summer',
            'type' => 'campaigns',
        ]));

        $response->assertOk();
        $response->assertViewHas('results');
    }

    public function test_search_clients(): void
    {
        Client::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Acme Corp',
            'company' => 'Acme Industries',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('search.store'), [
            'query' => 'Acme',
            'type' => 'clients',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_save_search_history(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('search.store'), [
            'query' => 'test query',
            'type' => 'all',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('search_history', [
            'user_id' => $this->user->id,
            'agency_id' => $this->agency->id,
            'query' => 'test query',
            'type' => 'all',
        ]);
    }

    public function test_get_recent_searches(): void
    {
        SearchHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('search.recent'));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'recent']);
        $response->assertJsonCount(3, 'recent');
    }

    public function test_search_requires_auth(): void
    {
        $response = $this->get(route('search.index'));
        $response->assertRedirect(route('login'));

        $response = $this->postJson(route('search.store'), [
            'query' => 'test',
        ]);
        $response->assertUnauthorized();
    }

    public function test_cross_agency_isolation(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
            'content' => 'Cross agency post',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('search.store'), [
            'query' => 'Cross agency',
            'type' => 'posts',
        ]);

        $response->assertOk();
        $body = $response->json();

        // Should not find results from another agency
        $this->assertEquals(0, $body['total_count']);
    }

    public function test_search_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('search.store'), [
            'query' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['query']);
    }

    public function test_delete_search_history(): void
    {
        $history = SearchHistory::factory()->create([
            'user_id' => $this->user->id,
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('search.destroy', $history->id));

        $response->assertOk();
        $this->assertDatabaseMissing('search_history', ['id' => $history->id]);
    }

    public function test_search_stats(): void
    {
        SearchHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('search.stats'));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'stats']);
    }
}
