<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_post_can_be_created(): void
    {
        $post = SocialPost::factory()->create();
        $this->assertDatabaseHas('social_posts', ['id' => $post->id]);
    }

    public function test_social_post_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $post = SocialPost::factory()->create(['agency_id' => $agency->id]);
        $this->assertEquals($agency->id, $post->agency->id);
    }

    public function test_social_post_belongs_to_social_account(): void
    {
        $account = SocialAccount::factory()->create();
        $post = SocialPost::factory()->create(['social_account_id' => $account->id]);
        $this->assertEquals($account->id, $post->socialAccount->id);
    }

    public function test_social_post_is_scheduled_returns_true(): void
    {
        $post = SocialPost::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
        $this->assertTrue($post->isScheduled());
    }

    public function test_social_post_is_published_returns_true(): void
    {
        $post = SocialPost::factory()->create(['status' => 'published']);
        $this->assertTrue($post->isPublished());
    }

    public function test_social_post_is_failed_returns_true(): void
    {
        $post = SocialPost::factory()->create(['status' => 'failed']);
        $this->assertTrue($post->isFailed());
    }

    public function test_social_post_scope_published(): void
    {
        SocialPost::factory()->create(['status' => 'published']);
        SocialPost::factory()->create(['status' => 'draft']);
        $this->assertCount(1, SocialPost::published()->get());
    }

    public function test_social_post_scope_scheduled(): void
    {
        SocialPost::factory()->create(['status' => 'scheduled', 'scheduled_at' => now()->addDay()]);
        SocialPost::factory()->create(['status' => 'scheduled', 'scheduled_at' => null]);
        $this->assertCount(1, SocialPost::scheduled()->get());
    }

    public function test_social_post_scope_by_platform(): void
    {
        SocialPost::factory()->create(['platform' => 'facebook']);
        SocialPost::factory()->create(['platform' => 'twitter']);
        $this->assertCount(1, SocialPost::byPlatform('facebook')->get());
    }

    public function test_social_post_engagement_rate_is_calculated(): void
    {
        $post = SocialPost::factory()->create([
            'metrics' => ['impressions' => 1000],
            'likes_count' => 50,
            'comments_count' => 10,
            'shares_count' => 5,
        ]);
        $this->assertEquals(6.5, $post->calculateEngagementRate());
    }
}
