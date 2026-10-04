<?php

namespace Tests\Feature\Social;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\SocialPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end verification that publishing flows through the driver registry
 * and stores the external identifier on the post.
 */
class PublishThroughDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_twitter_publish_stores_external_post_id_via_driver(): void
    {
        Http::fake([
            'api.twitter.com/*' => Http::response(['data' => ['id' => 'tw-777']], 201),
        ]);

        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->twitter()->create([
            'agency_id' => $agency->id,
            'access_token' => 'token',
            'token_expires_at' => null,
        ]);

        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'platform' => 'twitter',
            'content' => 'Hello from the driver registry',
            'status' => 'draft',
        ]);

        $result = app(SocialPostService::class)->publishPost($post);

        $this->assertTrue($result['success'], 'Publish should succeed: '.($result['message'] ?? ''));
        $this->assertSame('tw-777', $post->fresh()->external_post_id);
        $this->assertSame('published', $post->fresh()->status);
    }

    public function test_publish_fails_cleanly_when_driver_reports_failure(): void
    {
        Http::fake([
            'api.twitter.com/*' => Http::response(['detail' => 'Rate limited'], 429),
        ]);

        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->twitter()->create([
            'agency_id' => $agency->id,
            'access_token' => 'token',
            'token_expires_at' => null,
        ]);

        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'platform' => 'twitter',
            'content' => 'Will fail',
            'status' => 'draft',
        ]);

        $result = app(SocialPostService::class)->publishPost($post);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $post->fresh()->status);
        $this->assertNotNull($post->fresh()->error_message);
    }

    public function test_expired_token_blocks_publish_before_any_http_call(): void
    {
        Http::fake();

        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->twitter()->create([
            'agency_id' => $agency->id,
            'access_token' => 'token',
            'token_expires_at' => now()->subDay(),
        ]);

        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'platform' => 'twitter',
            'content' => 'Expired',
            'status' => 'draft',
        ]);

        $result = app(SocialPostService::class)->publishPost($post);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', strtolower((string) $result['message']));
        Http::assertNothingSent();
    }
}
