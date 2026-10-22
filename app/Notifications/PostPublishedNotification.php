<?php

namespace App\Notifications;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PostPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SocialPost $post) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Published Successfully')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your post has been published to {$this->post->platform}.")
            ->line('Content: '.Str::limit($this->post->content, 100))
            ->action('View Post', url("/social/posts/{$this->post->id}"))
            ->line('Keep up the great work!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'platform' => $this->post->platform,
            'message' => 'Post published successfully',
        ];
    }
}
