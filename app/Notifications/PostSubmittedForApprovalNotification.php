<?php

namespace App\Notifications;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PostSubmittedForApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SocialPost $post) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Submitted for Approval')
            ->line('A new post has been submitted for your approval.')
            ->line('Platform: '.ucfirst($this->post->platform))
            ->line('Content: '.Str::limit($this->post->content ?? '', 100))
            ->action('Review Post', url('/client/posts/'.$this->post->id.'/review'))
            ->line('Thank you!');
    }
}
