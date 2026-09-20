<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $platform,
        public readonly string $eventType,
        public readonly string $webhookId,
        public readonly array $payload,
        public readonly ?string $signature = null,
        public readonly bool $signatureValid = false,
    ) {}
}
