<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SocialPostPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $platform,
        public readonly string $postId,
        public readonly string $title,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'post_published',
            'platform' => $this->platform,
            'post_id' => $this->postId,
            'title' => $this->title,
            'message' => "Post published to {$this->platform}: {$this->title}",
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
