
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $billingUrl = route('billing.upgrade');

        return (new MailMessage)
            ->subject('Subscription Expired')
            ->greeting('Hello '.$notifiable->name)
            ->line('Your subscription has expired and your account has been downgraded to the free plan.')
            ->line('To restore your premium features, please renew your subscription.')
            ->action('Renew Subscription', $billingUrl)
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_expired',
            'message' => 'Your subscription has expired. Please renew to restore premium features.',
        ];
    }
}
