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

class ProcessScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public SocialPost $post) {}

    public function handle(): void
    {
        if ($this->post->status !== PostStatus::SCHEDULED->value) {
            Log::info("Post #{$this->post->id} is no longer scheduled, skipping.");

            return;
        }

        if ($this->post->scheduled_at && $this->post->scheduled_at->isFuture()) {
            Log::info("Post #{$this->post->id} scheduled for future, releasing back to queue.");
            $this->release(60);

            return;
        }

        PublishPost::dispatch($this->post);
    }
}
