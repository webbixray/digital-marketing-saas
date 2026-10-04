<?php

namespace Tests\Unit\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Contracts\SocialPlatformContract;
use App\Services\Social\Drivers\FacebookDriver;
use App\Services\Social\Drivers\TikTokDriver;
use App\Services\Social\Drivers\YouTubeDriver;
use App\Services\Social\Results\PlatformPublishResult;
use App\Services\Social\SocialPlatformManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SocialPlatformManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_all_registered_drivers(): void
    {
        $manager = app(SocialPlatformManager::class);

        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'] as $platform) {
            $this->assertTrue($manager->has($platform), "Missing driver: {$platform}");
            $this->assertInstanceOf(SocialPlatformContract::class, $manager->for($platform));
        }
    }

    public function test_it_returns_the_correct_driver_class_per_platform(): void
    {
        $manager = app(SocialPlatformManager::class);

        $this->assertInstanceOf(FacebookDriver::class, $manager->for('facebook'));
        $this->assertInstanceOf(TikTokDriver::class, $manager->for('tiktok'));
        $this->assertInstanceOf(YouTubeDriver::class, $manager->for('youtube'));
    }

    public function test_driver_names_and_labels_are_consistent(): void
    {
        foreach (app(SocialPlatformManager::class)->all() as $key => $driver) {
            $this->assertSame($key, $driver->name());
            $this->assertNotEmpty($driver->label());
        }
    }

    public function test_it_throws_for_an_unregistered_platform(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SocialPlatformManager::class)->for('myspace');
    }

    public function test_has_returns_false_for_unknown_platform(): void
    {
        $this->assertFalse(app(SocialPlatformManager::class)->has('threads'));
    }

    public function test_instagram_requires_media_to_publish(): void
    {
        $account = SocialAccount::factory()->instagram()->create();
        $post = SocialPost::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'instagram',
            'content' => 'No media here',
            'media' => [],
        ]);

        $result = app(SocialPlatformManager::class)->for('instagram')->publish($account, $post);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('image or video', (string) $result->error);
    }

    public function test_tiktok_requires_video_to_publish(): void
    {
        $account = SocialAccount::factory()->tiktok()->create();
        $post = SocialPost::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'tiktok',
            'content' => 'No video',
            'media' => [],
        ]);

        $result = app(SocialPlatformManager::class)->for('tiktok')->publish($account, $post);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('video', (string) $result->error);
    }

    public function test_pinterest_requires_an_image_and_board(): void
    {
        $account = SocialAccount::factory()->create(['platform' => 'pinterest', 'metadata' => []]);
        $post = SocialPost::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'pinterest',
            'content' => 'No image',
            'media' => [],
        ]);

        $result = app(SocialPlatformManager::class)->for('pinterest')->publish($account, $post);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('image', (string) $result->error);
    }

    public function test_publish_result_from_array_normalizes_identifier_keys(): void
    {
        foreach (['post_id', 'video_id', 'media_id', 'pin_id', 'tweet_id', 'id'] as $key) {
            $result = PlatformPublishResult::fromArray(['success' => true, $key => 'abc123']);

            $this->assertTrue($result->success);
            $this->assertSame('abc123', $result->externalId, "Failed for key {$key}");
        }
    }

    public function test_publish_result_to_array_is_backward_compatible(): void
    {
        $result = PlatformPublishResult::ok('42', 'https://example.com', platform: 'facebook');
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame('42', $array['external_id']);
        $this->assertSame('42', $array['post_id']);
        $this->assertSame('https://example.com', $array['url']);
    }

    public function test_expired_token_is_reported_by_validate(): void
    {
        $account = SocialAccount::factory()->facebook()->create([
            'token_expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($account->isExpired());
        $this->assertFalse(app(SocialPlatformManager::class)->for('facebook')->validateToken($account));
    }
}
