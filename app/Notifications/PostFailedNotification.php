<?php

namespace App\Notifications;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SocialPost $post,
        public string $errorMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Failed to Publish')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your post failed to publish to {$this->post->platform}.")
            ->line('Error: '.$this->errorMessage)
            ->action('View Post', url("/social/posts/{$this->post->id}"))
            ->line('Please check the post and try again.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'platform' => $this->post->platform,
            'message' => 'Post failed to publish',
            'error' => $this->errorMessage,
        ];
    }
}
