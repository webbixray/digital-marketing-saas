<?php

namespace Tests\Unit\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Drivers\AbstractPlatformDriver;
use App\Services\Social\Results\PlatformPublishResult;
use App\Services\Social\SocialPlatformManager;
use App\Services\Social\UnifiedCommentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedCommentsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_delegates_fetch_comments_to_the_manager(): void
    {
        $account = SocialAccount::factory()->facebook()->create();

        $fake = new class extends AbstractPlatformDriver
        {
            public array $fetched = [];

            public function name(): string
            {
                return 'facebook';
            }

            public function label(): string
            {
                return 'Facebook';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function publish(SocialAccount $a, SocialPost $p): PlatformPublishResult
            {
                return PlatformPublishResult::ok();
            }

            public function fetchComments(SocialAccount $a, string $postId, array $options = []): array
            {
                $this->fetched[] = $postId;

                return [['id' => 'c1', 'message' => 'hello']];
            }
        };

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(true);
        $manager->shouldReceive('for')->with('facebook')->andReturn($fake);

        $service = new UnifiedCommentsService($manager);
        $comments = $service->fetchComments($account, 'post-123');

        $this->assertCount(1, $comments);
        $this->assertSame('c1', $comments[0]['id']);
        $this->assertSame(['post-123'], $fake->fetched);
    }

    public function test_it_delegates_reply_to_the_manager(): void
    {
        $account = SocialAccount::factory()->facebook()->create();

        $fake = new class extends AbstractPlatformDriver
        {
            public function name(): string
            {
                return 'facebook';
            }

            public function label(): string
            {
                return 'Facebook';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function publish(SocialAccount $a, SocialPost $p): PlatformPublishResult
            {
                return PlatformPublishResult::ok();
            }

            public function replyToComment(SocialAccount $a, string $commentId, string $message): PlatformPublishResult
            {
                return PlatformPublishResult::ok('reply-1');
            }
        };

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(true);
        $manager->shouldReceive('for')->with('facebook')->andReturn($fake);

        $service = new UnifiedCommentsService($manager);

        $this->assertTrue($service->replyToComment($account, 'c1', 'Thanks!'));
    }

    public function test_it_returns_empty_for_an_unknown_platform_without_throwing(): void
    {
        $account = SocialAccount::factory()->create(['platform' => 'facebook']);

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(false);

        $service = new UnifiedCommentsService($manager);

        $this->assertSame([], $service->fetchComments($account, 'post-1'));
        $this->assertFalse($service->replyToComment($account, 'c1', 'hi'));
    }

    public function test_default_driver_returns_empty_comments_and_failed_reply(): void
    {
        $driver = new class extends AbstractPlatformDriver
        {
            public function name(): string
            {
                return 'linkedin';
            }

            public function label(): string
            {
                return 'LinkedIn';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function publish(SocialAccount $a, SocialPost $p): PlatformPublishResult
            {
                return PlatformPublishResult::ok();
            }
        };

        $account = SocialAccount::factory()->create();

        $this->assertSame([], $driver->fetchComments($account, 'x'));
        $this->assertFalse($driver->replyToComment($account, 'c', 'hi')->success);
    }
}
