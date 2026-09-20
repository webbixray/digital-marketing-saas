<?php

namespace App\Observers;

use App\Models\WebhookLog;
use Illuminate\Support\Facades\Log;

class WebhookLogObserver
{
    public function created(WebhookLog $webhookLog): void
    {
        // Log failed webhooks for monitoring
        if (! $webhookLog->is_success) {
            Log::warning('Webhook delivery failed', [
                'webhook_id' => $webhookLog->webhook_id,
                'event' => $webhookLog->event,
                'status_code' => $webhookLog->status_code,
                'error' => $webhookLog->error_message,
            ]);
        }
    }
}
