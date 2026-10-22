<?php

namespace App\Jobs\Social;

use App\Enums\PostStatus;
use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(): void
    {
        $failedPosts = SocialPost::where('status', PostStatus::FAILED->value)
            ->where('retry_count', '<', 3)
            ->limit(20)
            ->get();

        foreach ($failedPosts as $post) {
            PublishPost::dispatch($post);
        }

        Log::info("Retrying {$failedPosts->count()} failed posts.");
    }
}
