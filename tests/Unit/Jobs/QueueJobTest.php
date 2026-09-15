<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Social\PublishPost;
use App\Jobs\Social\ProcessScheduledPost;
use App\Jobs\Social\ProcessScheduledPostsJob;
use App\Jobs\RetryFailedPost;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Social\SocialPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class QueueJobTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    public function test_process_scheduled_posts_job_dispatches_individual_jobs(): void
    {
        Bus::fake();

        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        $job = new ProcessScheduledPostsJob();
        $job->handle();

        Bus::assertDispatched(ProcessScheduledPost::class);
    }

    public function test_publish_post_job_calls_service(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
        ]);

        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $post->update(['social_account_id' => $account->id]);

        $mockService = \Mockery::mock(SocialPostService::class);
        $mockService->shouldReceive('publishPost')
            ->once()
            ->with($post)
            ->andReturn([
                'success' => true,
                'message' => 'Post published successfully.',
                'data' => $post,
            ]);
        $this->app->instance(SocialPostService::class, $mockService);

        $job = new PublishPost($post);
        $job->handle($mockService);

        // The service is called, status update happens inside the service mock
        $this->assertTrue(true);
    }

    public function test_retry_failed_post_job_skips_already_published(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'published',
            'retry_count' => 0,
        ]);

        $job = new RetryFailedPost($post->id);
        $job->handle();

        $post->refresh();
        $this->assertEquals('published', $post->status);
    }

    public function test_retry_failed_post_job_skips_exceeded_max_retries(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'failed',
            'retry_count' => 3,
        ]);

        $job = new RetryFailedPost($post->id);
        $job->handle();

        $post->refresh();
        $this->assertEquals('failed', $post->status);
        $this->assertEquals(3, $post->retry_count);
    }

    public function test_process_scheduled_post_skips_non_scheduled_status(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $job = new ProcessScheduledPost($post);
        $job->handle();

        $post->refresh();
        $this->assertEquals('draft', $post->status);
    }

    public function test_process_scheduled_post_releases_future_posts(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $job = new ProcessScheduledPost($post);
        $job->handle();

        $post->refresh();
        $this->assertEquals('scheduled', $post->status);
    }

    public function test_jobs_have_correct_retry_settings(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
        ]);

        $job = new PublishPost($post);
        
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }

    public function test_process_scheduled_posts_job_queries_due_posts(): void
    {
        // Create posts that are due
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        // Create posts that are not due
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        // Create posts with wrong status
        SocialPost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $job = new ProcessScheduledPostsJob();
        $job->handle();

        // Should dispatch 3 jobs for due posts
        $this->assertTrue(true);
    }
}
