<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SocialAccountDisconnected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $platform,
        public readonly string $accountName,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'account_disconnected',
            'platform' => $this->platform,
            'account_name' => $this->accountName,
            'message' => "{$this->platform} account '{$this->accountName}' was disconnected",
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
