<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private Webhook $webhook,
        private WebhookDelivery $delivery
    ) {}

    public function handle(): void
    {
        $this->delivery->increment('attempts');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $this->generateSignature(),
                    'User-Agent' => 'DigitalMarketingsaas-Webhook/1.0',
                ])
                ->post($this->webhook->url, $this->delivery->payload);

            $this->delivery->update([
                'response_code' => $response->status(),
                'response_body' => $response->body(),
                'status' => $response->successful() ? 'success' : 'failed',
                'delivered_at' => now(),
            ]);

            if ($response->successful()) {
                $this->webhook->increment('total_calls');
                $this->webhook->update(['last_triggered_at' => now()]);
            } else {
                $this->webhook->increment('failed_calls');
                Log::warning("Webhook delivery failed", [
                    'webhook_id' => $this->webhook->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            $this->delivery->update([
                'status' => 'failed',
                'response_body' => $e->getMessage(),
            ]);

            $this->webhook->increment('failed_calls');

            Log::error("Webhook delivery exception", [
                'webhook_id' => $this->webhook->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function generateSignature(): string
    {
        return hash_hmac('sha256', json_encode($this->delivery->payload), $this->webhook->secret);
    }
}
