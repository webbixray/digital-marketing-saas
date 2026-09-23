<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\Social\SocialPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SocialPost $post,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SocialPostService $service): void
    {
        if (! $this->post->socialAccount) {
            Log::warning("SendPostJob: Post #{$this->post->id} has no connected social account.");
            $this->fail('No connected social account.');
            return;
        }

        try {
            $result = $service->publishPost($this->post);

            if (! $result['success']) {
                Log::warning("SendPostJob: publishPost returned failure for post #{$this->post->id}");
                throw new \RuntimeException($result['error'] ?? 'Unknown publishing error');
            }
        } catch (\Exception $e) {
            Log::error("SendPostJob failed for post #{$this->post->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->post->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $exception->getMessage(),
            'retry_count' => ($this->post->retry_count ?? 0) + 1,
        ]);

        Log::error("SendPostJob permanently failed for post #{$this->post->id}: {$exception->getMessage()}");
    }
}
