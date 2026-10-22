<?php

namespace Tests\Feature\Search;

use App\Models\Agency;
use App\Models\Client;
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
    }

    public function test_it_shows_search_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('search.index'));

        $response->assertOk();
        $response->assertViewIs('search.index');
    }

    public function test_it_searches_posts(): void
    {
        SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'content' => 'Test post content',
        ]);

        $response = $this->actingAs($this->user)->get(route('search.index', ['q' => 'Test', 'type' => 'posts']));

        $response->assertOk();
        $response->assertViewHas('results');
    }

    public function test_it_searches_clients(): void
    {
        Client::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Test Client',
        ]);

        $response = $this->actingAs($this->user)->get(route('search.index', ['q' => 'Test', 'type' => 'clients']));

        $response->assertOk();
        $response->assertViewHas('results');
    }

    public function test_it_returns_empty_for_no_query(): void
    {
        $response = $this->actingAs($this->user)->get(route('search.index'));

        $response->assertOk();
        $response->assertViewHas('results', []);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('search.index'));

        $response->assertRedirect(route('login'));
    }
}
