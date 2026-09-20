<?php

namespace App\Services\Webhooks;

use App\Models\WebhookProcessingLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookProcessor
{
    private const DEDUPLICATION_TTL = 300;

    public function process(
        string $platform,
        string $eventType,
        array $payload,
        ?string $signature = null,
        ?callable $handler = null
    ): WebhookProcessingLog {
        $webhookId = $this->generateWebhookId($platform, $payload);

        if ($this->isDuplicate($webhookId)) {
            Log::info('Webhook deduplicated (skipping)', [
                'platform' => $platform,
                'webhook_id' => $webhookId,
            ]);

            return $this->findExistingLog($webhookId) ?? new WebhookProcessingLog([
                'webhook_id' => $webhookId,
                'platform' => $platform,
                'status' => 'duplicate',
            ]);
        }

        $signatureValid = $this->verifySignature($platform, $payload, $signature);

        $log = WebhookProcessingLog::create([
            'platform' => $platform,
            'event_type' => $eventType,
            'webhook_id' => $webhookId,
            'signature' => $signature ? substr($signature, 0, 255) : null,
            'signature_valid' => $signatureValid,
            'payload' => $payload,
            'status' => 'pending',
            'attempt' => 0,
            'max_attempts' => 5,
        ]);

        event(new \App\Events\WebhookReceived(
            platform: $platform,
            eventType: $eventType,
            webhookId: $webhookId,
            payload: $payload,
            signature: $signature,
            signatureValid: $signatureValid,
        ));

        if ($handler) {
            dispatch(function () use ($log, $payload, $handler) {
                $log->markAsProcessing();
                try {
                    $handler($payload, $log);
                    $log->markAsCompleted();
                } catch (\Throwable $e) {
                    $log->markAsFailed($e->getMessage());
                    Log::error('Webhook handler failed', [
                        'platform' => $log->platform,
                        'webhook_id' => $log->webhook_id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            });
        }

        return $log;
    }

    private function generateWebhookId(string $platform, array $payload): string
    {
        $id = match ($platform) {
            'facebook' => $payload['entry'][0]['id'] ?? null,
            'instagram' => $payload['entry'][0]['id'] ?? null,
            'linkedin' => $payload['events'][0]['eventId'] ?? null,
            'tiktok' => $payload['data']['events'][0]['event_id'] ?? null,
            'youtube' => (string) ($payload['entry']['id'] ?? null),
            'telegram' => $payload['update_id'] ?? null,
            default => null,
        };

        if ($id) {
            return $platform . '_' . $id;
        }

        return $platform . '_' . md5(json_encode($payload) . now()->format('Y-m-d-H-i'));
    }

    private function isDuplicate(string $webhookId): bool
    {
        $cacheKey = "webhook_dedup:{$webhookId}";
        $exists = Cache::has($cacheKey);

        if (! $exists) {
            Cache::put($cacheKey, true, self::DEDUPLICATION_TTL);
        }

        if (! $exists) {
            $exists = WebhookProcessingLog::where('webhook_id', $webhookId)
                ->where('created_at', '>', now()->subSeconds(self::DEDUPLICATION_TTL * 2))
                ->exists();

            if ($exists) {
                Cache::put($cacheKey, true, self::DEDUPLICATION_TTL);
            }
        }

        return $exists;
    }

    private function findExistingLog(string $webhookId): ?WebhookProcessingLog
    {
        return WebhookProcessingLog::where('webhook_id', $webhookId)->first();
    }

    private function verifySignature(string $platform, array $payload, ?string $signature): bool
    {
        if (! $signature) {
            return config('webhooks.allow_unsigned', false);
        }

        $secret = match ($platform) {
            'facebook' => config('services.facebook.webhook_secret'),
            'instagram' => config('services.instagram.webhook_secret'),
            'linkedin' => config('services.linkedin.webhook_secret'),
            'tiktok' => config('services.tiktok.webhook_secret'),
            'youtube' => config('services.youtube.webhook_secret'),
            'telegram' => config('services.telegram.webhook_secret'),
            default => null,
        };

        if (! $secret) {
            Log::warning("No webhook secret configured for platform: {$platform}");
            return false;
        }

        $expected = hash_hmac('sha256', json_encode($payload), $secret);

        return hash_equals($expected, $signature);
    }

    public function verifyFacebookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.facebook.webhook_secret');
        if (! $secret) {
            return config('webhooks.allow_unsigned', false);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function verifyTikTokSignature(string $payload, string $signature): bool
    {
        $secret = config('services.tiktok.webhook_secret');
        if (! $secret) {
            return config('webhooks.allow_unsigned', false);
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
