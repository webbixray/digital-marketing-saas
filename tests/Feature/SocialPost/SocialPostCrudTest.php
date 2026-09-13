<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_social_post_index_requires_auth(): void
    {
        $response = $this->get(route('social.posts.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_social_post_index_loads(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.index'));
        $response->assertStatus(200);
    }

    public function test_social_post_show_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $post = SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post->id));
        $response->assertStatus(403);
    }

    public function test_social_post_store_requires_valid_data(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'content' => '',
        ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_social_post_creates_successfully(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'social_account_id' => $account->id,
            'content' => 'Test post content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', [
            'agency_id' => $this->agency->id,
            'content' => 'Test post content',
        ]);
    }
}
