<?php

namespace App\Listeners;

use App\Events\PostFailed;
use App\Notifications\SocialAccountDisconnected;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HandlePostFailure
{
    public function handle(PostFailed $event): void
    {
        $post = $event->post;

        Log::error('Post failed', [
            'post_id' => $post->id,
            'agency_id' => $post->agency_id,
            'platform' => $post->platform,
            'error' => $post->error_message,
        ]);

        // Notify agency owner of critical failures
        if ($post->retry_count >= 3) {
            $agency = $post->agency;
            if ($agency && $agency->owner) {
                // Could notify owner of repeated failures
            }
        }
    }
}
