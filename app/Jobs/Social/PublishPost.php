<?php

namespace App\Jobs\Social;

use App\Models\SocialPost;
use App\Services\Social\SocialPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(public SocialPost $post) {}

    public function handle(SocialPostService $service): void
    {
        try {
            $result = $service->publishPost($this->post);

            if (! $result['success']) {
                Log::warning("PublishPost job failed for post #{$this->post->id}");
            }
        } catch (\Exception $e) {
            Log::error("PublishPost job exception: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->post->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}
