<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ContentAsset;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
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

    public function test_it_shows_search_page(): void
    {
        $response = $this->get('/search');
        $response->assertStatus(200);
        $response->assertViewHas('results');
    }

    public function test_it_searches_posts(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'content' => 'Hello World',
        ]);
        $response = $this->get('/search?q=Hello');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('posts', $results);
        $this->assertCount(1, $results['posts']);
    }

    public function test_it_searches_campaigns(): void
    {
        Campaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Summer Campaign',
        ]);
        $response = $this->get('/search?q=Summer');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('campaigns', $results);
        $this->assertCount(1, $results['campaigns']);
    }

    public function test_it_searches_clients(): void
    {
        Client::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Acme Corp',
        ]);
        $response = $this->get('/search?q=Acme');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('clients', $results);
        $this->assertCount(1, $results['clients']);
    }

    public function test_it_searches_content_assets(): void
    {
        ContentAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Brand Guide',
        ]);
        $response = $this->get('/search?q=Brand');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('content', $results);
        $this->assertCount(1, $results['content']);
    }

    public function test_it_filters_by_type(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'content' => 'Test Post',
        ]);
        $response = $this->get('/search?q=Test&type=posts');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('posts', $results);
        $this->assertCount(1, $results['posts']);
        $this->assertArrayNotHasKey('campaigns', $results);
    }

    public function test_it_returns_empty_results_for_no_match(): void
    {
        $response = $this->get('/search?q=xyznonexistent');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $total = 0;
        foreach ($results as $type => $items) {
            $total += count($items);
        }
        $this->assertEquals(0, $total);
    }

    public function test_it_prevents_accessing_other_agency_search(): void
    {
        $otherAgency = Agency::factory()->create();
        SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
            'content' => 'Hello World',
        ]);
        $response = $this->get('/search?q=Hello');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $total = 0;
        foreach ($results as $type => $items) {
            $total += count($items);
        }
        $this->assertEquals(0, $total);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/search');
        $response->assertRedirect('/login');
    }
}
