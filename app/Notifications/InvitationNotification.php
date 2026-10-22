<?php

namespace App\Notifications;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Agency $agency) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitation to Join '.$this->agency->name)
            ->line("You've been invited to join {$this->agency->name} on ".config('app.name').'.')
            ->action('Register', url('/register'))
            ->line('Use your email address to register and accept the invitation.');
    }
}
