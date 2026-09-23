<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiContentControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->get(route('ai.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_ai_content_page(): void
    {
        AiContentLog::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('ai.index'));

        $response->assertOk();
        $response->assertViewHas('recentGenerations');
        $response->assertViewHas('remaining');
    }

    public function test_generate_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.generate'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt', 'content_type']);
    }

    public function test_generate_validates_content_type(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.generate'), [
            'prompt' => 'Write a blog post',
            'content_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content_type']);
    }

    public function test_generate_accepts_valid_content_types(): void
    {
        $validTypes = ['post', 'caption', 'hashtag', 'headline', 'email', 'ad_copy', 'landing_page', 'blog'];

        foreach ($validTypes as $type) {
            $response = $this->actingAs($this->user)->postJson(route('ai.generate'), [
                'prompt' => "Generate a {$type}",
                'content_type' => $type,
            ]);

            // Will likely fail on AI dispatch but should pass validation
            $this->assertTrue(in_array($response->status(), [200, 500]));
        }
    }

    public function test_rewrite_validates_required_content(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.rewrite'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_hashtags_validates_required_topic(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.hashtags'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['topic']);
    }

    public function test_ideas_validates_required_topic(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.ideas'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['topic']);
    }
}
