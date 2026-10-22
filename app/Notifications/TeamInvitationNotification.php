<?php

namespace App\Notifications;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Agency $agency,
        public string $temporaryPassword,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been invited to {$this->agency->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You've been invited to join {$this->agency->name} on DigitalMarketingSaaS.")
            ->line('Your temporary password is: '.$this->temporaryPassword)
            ->action('Login', url('/login'))
            ->line('Please change your password after first login.')
            ->line('If you did not expect this invitation, no action is needed.');
    }
}
