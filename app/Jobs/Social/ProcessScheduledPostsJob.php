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

class ProcessScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(): void
    {
        $posts = SocialPost::where('status', PostStatus::SCHEDULED->value)
            ->where('scheduled_at', '<=', now())
            ->limit(50)
            ->get();

        foreach ($posts as $post) {
            ProcessScheduledPost::dispatch($post);
        }

        Log::info("Dispatched {$posts->count()} scheduled posts for processing.");
    }
}
