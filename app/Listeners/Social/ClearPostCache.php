<?php

namespace App\Listeners\Social;

use App\Events\PostFailed;
use App\Events\PostPublished;
use App\Events\PostScheduled;
use Illuminate\Support\Facades\Cache;

class ClearPostCache
{
    public function handle(object $event): void
    {
        $agencyId = $event->post->agency_id ?? null;
        if ($agencyId) {
            Cache::tags(["agency:{$agencyId}", 'posts'])->flush();
        }
    }

    public function handlePostPublished(PostPublished $event): void
    {
        $this->handle($event);
    }

    public function handlePostFailed(PostFailed $event): void
    {
        $this->handle($event);
    }

    public function handlePostScheduled(PostScheduled $event): void
    {
        $this->handle($event);
    }
}
