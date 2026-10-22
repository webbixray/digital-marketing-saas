<?php

namespace Tests\Feature\SocialPost;

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

    private SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'twitter',
        ]);
    }

    public function test_it_lists_posts(): void
    {
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('social.posts.index'));

        $response->assertOk();
        $response->assertViewIs('social.posts.index');
        $response->assertViewHas('posts');
    }

    public function test_it_creates_a_post(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'social_account_id' => $this->account->id,
            'content' => 'Test post content',
            'hashtags' => ['test', 'social'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', [
            'content' => 'Test post content',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_validates_post_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), []);

        $response->assertSessionHasErrors(['social_account_id', 'content']);
    }

    public function test_it_shows_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));

        $response->assertOk();
        $response->assertViewIs('social.posts.show');
        $response->assertViewHas('post');
    }

    public function test_it_prevents_showing_other_agency_posts(): void
    {
        $otherAgency = Agency::factory()->create();
        $post = SocialPost::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));

        $response->assertForbidden();
    }

    public function test_it_edits_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.edit', $post));

        $response->assertOk();
        $response->assertViewIs('social.posts.edit');
    }

    public function test_it_updates_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->put(route('social.posts.update', $post), [
            'content' => 'Updated content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_it_deletes_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('social.posts.destroy', $post));

        $response->assertRedirect(route('social.posts.index'));
        $this->assertSoftDeleted('social_posts', ['id' => $post->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('social.posts.index'));

        $response->assertRedirect(route('login'));
    }
}
