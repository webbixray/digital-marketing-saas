
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $invoiceId
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $retryUrl = route('billing.retry', ['invoice' => $this->invoiceId]);

        return (new MailMessage)
            ->subject('Payment Failed - Action Required')
            ->greeting('Hello '.$notifiable->name)
            ->line('We were unable to process your payment.')
            ->line('Please update your payment method to avoid service interruption.')
            ->action('Update Payment Method', $retryUrl)
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'type' => 'payment_failed',
            'message' => 'Your payment failed. Please update your payment method.',
        ];
    }
}
