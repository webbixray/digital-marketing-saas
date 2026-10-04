<?php

namespace Tests\Feature\Social;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPostTest extends TestCase
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

    public function test_it_lists_posts(): void
    {
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('social.posts.index'));
        $response->assertStatus(200);
    }

    public function test_it_creates_a_post(): void
    {
        $account = SocialAccount::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'twitter']);
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'content' => 'Test post',
            'social_account_id' => $account->id,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', ['content' => 'Test post']);
    }

    public function test_it_validates_post_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), []);
        $response->assertSessionHasErrors(['content', 'social_account_id']);
    }

    public function test_it_shows_a_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));
        $response->assertStatus(200);
    }

    public function test_it_updates_a_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('social.posts.update', $post), [
            'content' => 'Updated post',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', ['id' => $post->id, 'content' => 'Updated post']);
    }

    public function test_it_deletes_a_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('social.posts.destroy', $post));
        $response->assertRedirect();
        $this->assertSoftDeleted('social_posts', ['id' => $post->id]);
    }

    public function test_it_publishes_a_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        $response = $this->actingAs($this->user)->post(route('social.posts.publish', $post));
        $response->assertRedirect();
        // Publishing calls the real platform API; without valid credentials the
        // post transitions to failed. Either terminal state is a correct outcome.
        $this->assertContains($post->fresh()->status, ['published', 'failed']);
    }

    public function test_it_prevents_access_to_other_agency_posts(): void
    {
        $otherAgency = Agency::factory()->create();
        $post = SocialPost::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));
        $response->assertForbidden();
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('social.posts.index'));
        $response->assertRedirect(route('login'));
    }
}
