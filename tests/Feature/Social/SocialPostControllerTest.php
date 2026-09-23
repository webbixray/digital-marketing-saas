<?php

namespace Tests\Feature\Social;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPostControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;
    private SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->account = SocialAccount::factory()->create(['agency_id' => $this->agency->id]);
    }

    /** Test it lists posts for the agency. */
    public function test_it_lists_posts(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.index'));

        $response->assertOk();
        $response->assertViewIs('social.posts.index');
        $response->assertViewHas('posts');
    }

    /** Test it filters posts by status. */
    public function test_it_filters_posts_by_status(): void
    {
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'published',
        ]);
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.index', ['status' => 'published']));

        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(2, $posts);
    }

    /** Test it filters posts by platform. */
    public function test_it_filters_posts_by_platform(): void
    {
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'facebook',
        ]);
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'platform' => 'twitter',
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.index', ['platform' => 'facebook']));

        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(2, $posts);
    }

    /** Test it creates a post with scheduling. */
    public function test_it_creates_scheduled_post(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'social_account_id' => $this->account->id,
            'content' => 'Scheduled test post',
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertRedirect(route('social.posts.index'));
        $this->assertDatabaseHas('social_posts', [
            'content' => 'Scheduled test post',
            'status' => 'scheduled',
        ]);
    }

    /** Test it creates a draft post. */
    public function test_it_creates_draft_post(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'social_account_id' => $this->account->id,
            'content' => 'Draft test post',
        ]);

        $response->assertRedirect(route('social.posts.index'));
        $this->assertDatabaseHas('social_posts', [
            'content' => 'Draft test post',
            'status' => 'draft',
        ]);
    }

    /** Test it validates post creation. */
    public function test_it_validates_post_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.posts.store'), []);

        $response->assertSessionHasErrors(['social_account_id', 'content']);
    }

    /** Test it prevents creating post for other agency account. */
    public function test_it_prevents_cross_agency_post_creation(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherAccount = SocialAccount::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->post(route('social.posts.store'), [
            'social_account_id' => $otherAccount->id,
            'content' => 'Test post',
        ]);

        $response->assertForbidden();
    }

    /** Test it shows a post with campaigns. */
    public function test_it_shows_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $post->campaigns()->attach($campaign->id);

        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));

        $response->assertOk();
        $response->assertViewIs('social.posts.show');
        $response->assertViewHas('post');
    }

    /** Test it updates a post. */
    public function test_it_updates_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->put(route('social.posts.update', $post), [
            'content' => 'Updated content',
        ]);

        $response->assertRedirect(route('social.posts.index'));
        $this->assertDatabaseHas('social_posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    /** Test it deletes a post. */
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

    /** Test it prevents access to other agency posts. */
    public function test_it_prevents_access_to_other_agency_posts(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherAccount = SocialAccount::factory()->create(['agency_id' => $otherAgency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
            'social_account_id' => $otherAccount->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('social.posts.show', $post));

        $response->assertForbidden();
    }

    /** Test it publishes a draft post. */
    public function test_it_publishes_a_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->post(route('social.posts.publish', $post));

        // Publish may succeed or fail depending on API, but should redirect
        $response->assertRedirect();
        $post->refresh();
        $this->assertContains($post->status, ['published', 'failed']);
    }

    /** Test it retries a failed post. */
    public function test_it_retries_failed_post(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'failed',
            'retry_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('social.posts.retry', $post));

        // Retry may succeed or fail, but should redirect
        $response->assertRedirect();
    }

    /** Test it scores a post quality. */
    public function test_it_scores_post_quality(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('social.posts.score', $post));

        $response->assertOk();
        $response->assertJsonStructure(['score', 'label']);
    }

    /** Test approval workflow: submit for approval. */
    public function test_approval_submit(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
        ]);
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->postJson(route('approvals.submit', ['post' => $post->id]), [
            'client_id' => $client->id,
        ]);

        $response->assertOk();
        $post->refresh();
        $this->assertEquals('pending', $post->approval_status);
    }

    /** Test approval workflow: approve post. */
    public function test_approval_approve(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('approvals.approve', ['post' => $post->id]), [
            'notes' => 'Looks great!',
        ]);

        $response->assertOk();
        $post->refresh();
        $this->assertEquals('approved', $post->approval_status);
    }

    /** Test approval workflow: reject post. */
    public function test_approval_reject(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('approvals.reject', ['post' => $post->id]), [
            'feedback' => 'Please revise the content',
        ]);

        $response->assertOk();
        $post->refresh();
        $this->assertEquals('rejected', $post->approval_status);
    }

    /** Test approval workflow: list pending. */
    public function test_approval_pending_list(): void
    {
        $account = SocialAccount::factory()->create(['agency_id' => $this->agency->id]);
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $account->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('approvals.pending'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    /** Test it requires authentication. */
    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('social.posts.index'));

        $response->assertRedirect(route('login'));
    }

    /** Test it requires agency membership. */
    public function test_it_requires_agency(): void
    {
        $noAgencyUser = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($noAgencyUser)->get(route('social.posts.index'));

        $response->assertForbidden();
    }

    /** Test create page loads with accounts and campaigns. */
    public function test_create_page_loads(): void
    {
        Campaign::factory()->count(2)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->get(route('social.posts.create'));

        $response->assertOk();
        $response->assertViewIs('social.posts.create');
        $response->assertViewHas('accounts');
        $response->assertViewHas('campaigns');
    }
}
