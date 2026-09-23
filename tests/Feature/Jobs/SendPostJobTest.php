<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendPostJob;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Social\SocialPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SendPostJobTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;
    private SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_send_post_job_dispatches_successfully(): void
    {
        Bus::fake();

        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'scheduled',
        ]);

        SendPostJob::dispatch($post);

        Bus::assertDispatched(SendPostJob::class, function ($job) use ($post) {
            return $job->post->id === $post->id;
        });
    }

    public function test_send_post_job_validates_platform_connection(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'scheduled',
        ]);

        // Simulate deleted social account (the post's social_account_id points to non-existent record)
        SocialAccount::where('id', $this->account->id)->delete();

        $post->refresh();
        // At this point socialAccount relation returns null because the account was deleted
        $this->assertNull($post->socialAccount);

        $mockService = \Mockery::mock(SocialPostService::class);
        $mockService->shouldNotReceive('publishPost');

        $job = new SendPostJob($post);
        $job->handle($mockService);

        // Job should have called $this->fail() which marks the job as failed
        // Verify it didn't throw an exception and the service wasn't called
        $this->assertTrue(true);
    }

    public function test_send_post_job_handles_retry(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'scheduled',
        ]);

        $mockService = \Mockery::mock(SocialPostService::class);
        $mockService->shouldReceive('publishPost')
            ->once()
            ->andThrow(new \RuntimeException('Temporary API error'));

        $job = new SendPostJob($post);

        try {
            $job->handle($mockService);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Temporary API error', $e->getMessage());
        }

        // Verify job has retry configuration
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    public function test_send_post_job_handles_failure(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'scheduled',
        ]);

        $job = new SendPostJob($post);
        $exception = new \RuntimeException('Permanent failure: API down');
        $job->failed($exception);

        $post->refresh();
        $this->assertEquals('failed', $post->status);
        $this->assertNotNull($post->failed_at);
        $this->assertEquals('Permanent failure: API down', $post->error_message);
        $this->assertGreaterThanOrEqual(1, $post->retry_count);
    }

    public function test_send_post_job_is_queueable(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'scheduled',
        ]);

        $job = new SendPostJob($post);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
        $this->assertEquals(120, $job->timeout);
    }

    public function test_send_post_job_calls_service_on_success(): void
    {
        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->account->id,
            'status' => 'scheduled',
        ]);

        $mockService = \Mockery::mock(SocialPostService::class);
        $mockService->shouldReceive('publishPost')
            ->once()
            ->with($post)
            ->andReturn(['success' => true]);

        $job = new SendPostJob($post);
        $job->handle($mockService);

        // If we get here without exception, the job succeeded
        $this->assertTrue(true);
    }
}
