<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Analytics\PlatformMetricsService;
use App\Services\Social\Drivers\AbstractPlatformDriver;
use App\Services\Social\Results\PlatformPublishResult;
use App\Services\Social\SocialPlatformManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_throw_for_an_unregistered_platform(): void
    {
        $account = SocialAccount::factory()->create(['platform' => 'facebook']);

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(false);

        // Should be a silent no-op, never an exception.
        (new PlatformMetricsService($manager))->fetchAndStoreMetrics($account);

        $this->assertTrue(true);
    }

    public function test_it_stores_followers_into_account_metadata(): void
    {
        $account = SocialAccount::factory()->facebook()->create(['metadata' => []]);

        $driver = $this->fakeDriverReturning(['followers_count' => 1234]);

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(true);
        $manager->shouldReceive('for')->with('facebook')->andReturn($driver);

        (new PlatformMetricsService($manager))->fetchAndStoreMetrics($account);

        $this->assertSame(1234, $account->fresh()->metadata['followers_count']);
        $this->assertArrayHasKey('last_synced_at', $account->fresh()->metadata);
    }

    public function test_it_matches_post_metrics_by_external_post_id(): void
    {
        $account = SocialAccount::factory()->facebook()->create();

        $post = SocialPost::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'status' => 'published',
            'external_post_id' => 'ext-999',
            'metrics' => [],
        ]);

        $driver = $this->fakeDriverReturning([
            'posts' => [
                'ext-999' => ['likes' => 42, 'comments' => 7, 'shares' => 3, 'impressions' => 500],
            ],
        ]);

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(true);
        $manager->shouldReceive('for')->with('facebook')->andReturn($driver);

        (new PlatformMetricsService($manager))->fetchAndStoreMetrics($account);

        $metrics = $post->fresh()->metrics;
        $this->assertSame(42, $metrics['likes']);
        $this->assertSame(7, $metrics['comments']);
        $this->assertSame(3, $metrics['shares']);
    }

    public function test_it_ignores_metrics_for_unknown_external_ids(): void
    {
        $account = SocialAccount::factory()->facebook()->create();

        $post = SocialPost::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'status' => 'published',
            'external_post_id' => 'ext-1',
            'metrics' => ['likes' => 0],
        ]);

        $driver = $this->fakeDriverReturning([
            'posts' => ['some-other-id' => ['likes' => 999]],
        ]);

        $manager = $this->mock(SocialPlatformManager::class);
        $manager->shouldReceive('has')->with('facebook')->andReturn(true);
        $manager->shouldReceive('for')->with('facebook')->andReturn($driver);

        (new PlatformMetricsService($manager))->fetchAndStoreMetrics($account);

        $this->assertSame(0, $post->fresh()->metrics['likes']);
    }

    private function fakeDriverReturning(array $metrics): object
    {
        return new class($metrics) extends AbstractPlatformDriver
        {
            public function __construct(private readonly array $metrics) {}

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

            public function fetchMetrics(SocialAccount $a, ?string $postId = null): array
            {
                return $this->metrics;
            }
        };
    }
}
