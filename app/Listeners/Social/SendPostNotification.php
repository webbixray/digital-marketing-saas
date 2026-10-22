<?php

namespace App\Listeners\Social;

use App\Events\PostFailed;
use App\Events\PostPublished;
use App\Models\User;
use App\Notifications\PostFailedNotification;
use App\Notifications\PostPublishedNotification;
use Illuminate\Support\Facades\Notification;

class SendPostNotification
{
    public function handlePostPublished(PostPublished $event): void
    {
        $agency = $event->post->agency;
        $recipients = User::where('agency_id', $agency->id)->get();
        Notification::send($recipients, new PostPublishedNotification($event->post));
    }

    public function handlePostFailed(PostFailed $event): void
    {
        $agency = $event->post->agency;
        $recipients = User::where('agency_id', $agency->id)
            ->whereIn('role', ['owner', 'admin'])
            ->get();
        Notification::send($recipients, new PostFailedNotification($event->post, $event->errorMessage));
    }
}
