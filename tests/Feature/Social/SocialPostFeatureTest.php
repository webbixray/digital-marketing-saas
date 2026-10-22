<?php

namespace Tests\Feature\Social;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPostFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    private SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->account = SocialAccount::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_it_lists_social_posts(): void
    {
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('social.posts.index'));
        $response->assertStatus(200);
    }

    public function test_it_creates_social_post(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'content' => 'Test post content',
            'social_account_id' => $this->account->id,
            'platform' => 'facebook',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', ['content' => 'Test post content']);
    }

    public function test_it_validates_post_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), []);
        $response->assertSessionHasErrors();
    }

    public function test_it_shows_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));
        $response->assertStatus(200);
    }

    public function test_it_updates_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('social.posts.update', $post), [
            'content' => 'Updated content',
        ]);
        $response->assertRedirect();
    }

    public function test_it_deletes_post(): void
    {
        $post = SocialPost::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('social.posts.destroy', $post));
        $response->assertRedirect();
        $this->assertSoftDeleted('social_posts', ['id' => $post->id]);
    }

    public function test_it_prevents_unauthorized_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $post = SocialPost::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));
        $response->assertForbidden();
    }

    public function test_it_filters_by_status(): void
    {
        SocialPost::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        SocialPost::factory()->create(['agency_id' => $this->agency->id, 'status' => 'published']);
        $response = $this->actingAs($this->user)->get(route('social.posts.index', ['status' => 'draft']));
        $response->assertStatus(200);
    }

    public function test_it_filters_by_platform(): void
    {
        SocialPost::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'facebook']);
        SocialPost::factory()->create(['agency_id' => $this->agency->id, 'platform' => 'twitter']);
        $response = $this->actingAs($this->user)->get(route('social.posts.index', ['platform' => 'facebook']));
        $response->assertStatus(200);
    }
}
