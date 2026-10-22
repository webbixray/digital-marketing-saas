<?php

namespace App\Notifications;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PostApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SocialPost $post) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Approved')
            ->line('Your post has been approved.')
            ->line('Platform: '.ucfirst($this->post->platform))
            ->line('Content: '.Str::limit($this->post->content ?? '', 100))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'type' => 'post_approved',
            'message' => 'Post approved: '.Str::limit($this->post->content ?? '', 50),
        ];
    }
}
