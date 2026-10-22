<?php

namespace App\Jobs;

use App\Events\PostFailed;
use App\Events\PostPublished;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\SocialPlatformManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     */
    public const MAX_RETRIES = 3;

    /**
     * Base delay in seconds (will be multiplied by attempt number for exponential backoff).
     */
    public const BASE_DELAY = 60;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        public readonly int $postId,
        public readonly int $attempt = 1,
    ) {}

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);

        if (! $post) {
            Log::warning("RetryFailedPost: Post {$this->postId} not found");

            return;
        }

        if ($post->status === 'published') {
            Log::info("RetryFailedPost: Post {$this->postId} already published, skipping retry");

            return;
        }

        if ($post->retry_count >= self::MAX_RETRIES) {
            Log::warning("RetryFailedPost: Post {$this->postId} exceeded max retries");

            return;
        }

        $account = SocialAccount::find($post->social_account_id);
        if (! $account) {
            Log::error("RetryFailedPost: Social account {$post->social_account_id} not found for post {$this->postId}");

            return;
        }

        // Update status to publishing
        $post->update([
            'status' => 'publishing',
            'retry_count' => $post->retry_count + 1,
        ]);

        try {
            $result = $this->publish($post, $account);

            if ($result['success']) {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'external_post_id' => $result['post_id'] ?? $result['video_id'] ?? $result['media_id'] ?? null,
                    'platform_response' => $result,
                    'error_message' => null,
                    'failed_at' => null,
                ]);

                event(new PostPublished($post));
                Log::info("RetryFailedPost: Post {$this->postId} published successfully on attempt {$this->attempt}");
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $post->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            event(new PostFailed($post));

            // Schedule next retry with exponential backoff
            if ($this->attempt < self::MAX_RETRIES) {
                $delay = self::BASE_DELAY * pow(2, $this->attempt); // 60s, 120s, 240s
                self::dispatch($this->postId, $this->attempt + 1)
                    ->delay(now()->addSeconds($delay))
                    ->onQueue('posting');
            }

            Log::error("RetryFailedPost: Post {$this->postId} failed attempt {$this->attempt}: {$e->getMessage()}");
        }
    }

    /**
     * Publish post based on platform via the central driver registry.
     */
    private function publish(SocialPost $post, SocialAccount $account): array
    {
        if ($account->isExpired()) {
            return ['success' => false, 'error' => "Access token for account #{$account->id} has expired. Please reconnect."];
        }

        try {
            $result = app(SocialPlatformManager::class)->for($post->platform)->publish($account, $post);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return $result->toArray();
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RetryFailedPost job failed for post {$this->postId}: {$exception->getMessage()}");

        $post = SocialPost::find($this->postId);
        if ($post) {
            $post->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
