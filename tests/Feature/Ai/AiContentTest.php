<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiContentTest extends TestCase
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

    public function test_it_shows_ai_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('ai.index'));

        $response->assertOk();
        $response->assertViewIs('ai.index');
        $response->assertViewHas('recentGenerations');
    }

    public function test_it_validates_generate_request(): void
    {
        $response = $this->actingAs($this->user)->post(route('ai.generate'), []);

        $response->assertSessionHasErrors(['prompt', 'content_type']);
    }

    public function test_it_validates_rewrite_request(): void
    {
        $response = $this->actingAs($this->user)->post(route('ai.rewrite'), []);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_it_validates_hashtags_request(): void
    {
        $response = $this->actingAs($this->user)->post(route('ai.hashtags'), []);

        $response->assertSessionHasErrors(['topic']);
    }

    public function test_it_validates_ideas_request(): void
    {
        $response = $this->actingAs($this->user)->post(route('ai.ideas'), []);

        $response->assertSessionHasErrors(['topic']);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('ai.index'));

        $response->assertRedirect(route('login'));
    }
}
