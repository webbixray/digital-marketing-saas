<?php

namespace App\Notifications;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuotaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Agency $agency,
        public string $feature,
        public int $percentage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Quota Warning')
            ->line("You've used {$this->percentage}% of your {$this->feature} quota.")
            ->action('Upgrade Plan', url('/agency/billing'))
            ->line('Upgrade to continue without interruption.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'feature' => $this->feature,
            'percentage' => $this->percentage,
            'message' => "You've used {$this->percentage}% of your {$this->feature} quota.",
            'url' => '/agency/billing',
        ];
    }
}
