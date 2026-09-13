<?php

namespace App\Jobs;

use App\Events\PostFailed;
use App\Events\PostPublished;
use App\Models\SocialPost;
use App\Models\SocialAccount;
use App\Services\Social\FacebookApiService;
use App\Services\Social\InstagramApiService;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\PinterestApiService;
use App\Services\Social\TikTokApiService;
use App\Services\Social\TwitterApiService;
use App\Services\Social\YouTubeApiService;
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

        if (!$post) {
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
        if (!$account) {
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
     * Publish post based on platform.
     */
    private function publish(SocialPost $post, SocialAccount $account): array
    {
        return match ($post->platform) {
            'facebook' => app(FacebookApiService::class)->postText(
                $account->platform_account_id,
                $account->access_token,
                $post->content,
            ),
            'instagram' => app(InstagramApiService::class)->post(
                $account->platform_account_id,
                $account->access_token,
                ['caption' => $post->content, ...$this->getMediaOptions($post)],
            ),
            'twitter' => app(TwitterApiService::class)->postTweet($post->content),
            'linkedin' => app(LinkedInApiService::class)->share(
                $account->access_token,
                'urn:li:person:' . $account->platform_account_id,
                $post->content,
            ),
            'tiktok' => app(TikTokApiService::class)->publishVideo(
                $account->access_token,
                $post->media['video_url'] ?? '',
                $post->content,
            ),
            'pinterest' => app(PinterestApiService::class)->createPin(
                $account->access_token,
                $post->metadata['board_id'] ?? '',
                $post->content,
                $post->content,
                $post->media['image_url'] ?? '',
            ),
            'youtube' => app(YouTubeApiService::class)->uploadVideo(
                $account->access_token,
                $post->media['video'] ?? null,
                $post->content,
            ),
            default => ['success' => false, 'error' => "Unsupported platform: {$post->platform}"],
        };
    }

    /**
     * Get media options for post.
     */
    private function getMediaOptions(SocialPost $post): array
    {
        $options = [];

        if (!empty($post->media['image_url'])) {
            $options['image_url'] = $post->media['image_url'];
        }
        if (!empty($post->media['video_url'])) {
            $options['video_url'] = $post->media['video_url'];
        }

        return $options;
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
