<?php

namespace App\Listeners\Social;

use App\Events\PostFailed;
use App\Events\PostPublished;
use App\Events\PostScheduled;
use App\Models\ActivityLog;

class LogPostActivity
{
    public function handlePostPublished(PostPublished $event): void
    {
        ActivityLog::create([
            'agency_id' => $event->post->agency_id,
            'action' => 'post.published',
            'description' => "Post #{$event->post->id} published to {$event->post->platform}",
            'subject_type' => SocialPost::class,
            'subject_id' => $event->post->id,
            'metadata' => ['results' => $event->results],
        ]);
    }

    public function handlePostFailed(PostFailed $event): void
    {
        ActivityLog::create([
            'agency_id' => $event->post->agency_id,
            'action' => 'post.failed',
            'description' => "Post #{$event->post->id} failed: {$event->errorMessage}",
            'subject_type' => SocialPost::class,
            'subject_id' => $event->post->id,
            'metadata' => ['error' => $event->errorMessage, 'attempt' => $event->attemptNumber],
        ]);
    }

    public function handlePostScheduled(PostScheduled $event): void
    {
        ActivityLog::create([
            'agency_id' => $event->post->agency_id,
            'action' => 'post.scheduled',
            'description' => "Post #{$event->post->id} scheduled",
            'subject_type' => SocialPost::class,
            'subject_id' => $event->post->id,
            'metadata' => ['scheduled_at' => $event->post->scheduled_at],
        ]);
    }
}
