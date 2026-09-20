<?php

namespace App\Listeners;

use App\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;

class LogWebhookAttempt
{
    public function handle(WebhookReceived $event): void
    {
        // Log the webhook attempt (the actual DB log is created in WebhookProcessor)
        Log::info('Webhook received', [
            'platform' => $event->platform,
            'event_type' => $event->eventType,
            'webhook_id' => $event->webhookId,
            'signature_valid' => $event->signatureValid,
        ]);

        if (! $event->signatureValid) {
            Log::warning('Webhook signature validation failed', [
                'platform' => $event->platform,
                'webhook_id' => $event->webhookId,
            ]);
        }
    }
}
