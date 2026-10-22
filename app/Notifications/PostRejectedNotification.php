<?php

namespace App\Notifications;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PostRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SocialPost $post,
        private readonly string $feedback
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Needs Revision')
            ->line('Your post has been rejected with feedback.')
            ->line('Platform: '.ucfirst($this->post->platform))
            ->line('Content: '.Str::limit($this->post->content ?? '', 100))
            ->line('Feedback: '.$this->feedback)
            ->line('Please revise and resubmit.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'type' => 'post_rejected',
            'message' => 'Post rejected: '.Str::limit($this->post->content ?? '', 50),
            'feedback' => $this->feedback,
        ];
    }
}
