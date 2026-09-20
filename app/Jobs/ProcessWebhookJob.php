<?php

namespace App\Jobs;

use App\Models\WebhookProcessingLog;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     * Retry delays: 60s, 300s, 900s, 3600s, max 5 attempts.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Exponential backoff delays in seconds for each attempt.
     */
    public const RETRY_DELAYS = [60, 300, 900, 3600, 3600];

    public int $tries = self::MAX_ATTEMPTS;

    public int $timeout = 60;

    public int $maxExceptions = self::MAX_ATTEMPTS;

    /**
     * Calculate the backoff delay for the current attempt.
     * 
     * Returns exponential backoff: 60s, 300s, 900s, 3600s, 3600s
     */
    public function backoff(): int
    {
        $attempt = max($this->attempts() - 1, 0);
        return self::RETRY_DELAYS[min($attempt, count(self::RETRY_DELAYS) - 1)];
    }

    public function __construct(
        public readonly int $webhookLogId,
    ) {}

    public function handle(): void
    {
        $log = WebhookProcessingLog::find($this->webhookLogId);

        if (! $log) {
            Log::warning("WebhookProcessingLog not found: {$this->webhookLogId}");
            return;
        }

        if ($log->isCompleted()) {
            Log::info("Webhook already completed: {$log->webhook_id}");
            return;
        }

        if (! $log->canRetry()) {
            Log::info("Webhook cannot be retried: {$log->webhook_id}");
            return;
        }

        $log->markAsProcessing();
        $log->incrementAttempt();

        $startTime = microtime(true);

        try {
            // Dispatch to platform-specific handler
            $result = $this->dispatchToHandler($log);

            $responseTime = (int) round((microtime(true) - $startTime) * 1000);

            if ($result) {
                $log->markAsCompleted();
                Log::info("Webhook processed successfully", [
                    'webhook_id' => $log->webhook_id,
                    'platform' => $log->platform,
                    'attempt' => $log->attempt,
                    'response_time_ms' => $responseTime,
                ]);
            } else {
                throw new \RuntimeException('Handler returned false');
            }
        } catch (\Throwable $e) {
            $responseTime = (int) round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            Log::error("Webhook processing failed", [
                'webhook_id' => $log->webhook_id,
                'platform' => $log->platform,
                'attempt' => $log->attempt,
                'error' => $errorMessage,
                'response_time_ms' => $responseTime,
            ]);

            if ($log->attempt >= self::MAX_ATTEMPTS) {
                $log->markAsDeadLetter("Max attempts reached: {$errorMessage}");
                Log::critical("Webhook moved to dead letter queue", [
                    'webhook_id' => $log->webhook_id,
                    'platform' => $log->platform,
                    'attempts' => $log->attempt,
                ]);
            } else {
                $log->markAsFailed($errorMessage);
                // Re-throw to trigger Laravel's retry mechanism
                throw $e;
            }
        }
    }

    /**
     * Dispatch webhook to the appropriate platform handler.
     */
    private function dispatchToHandler(WebhookProcessingLog $log): bool
    {
        return match ($log->platform) {
            'facebook' => $this->handleFacebook($log),
            'instagram' => $this->handleInstagram($log),
            'linkedin' => $this->handleLinkedIn($log),
            'tiktok' => $this->handleTikTok($log),
            'youtube' => $this->handleYouTube($log),
            'telegram' => $this->handleTelegram($log),
            'workflow' => $this->handleWorkflow($log),
            default => $this->handleGeneric($log),
        };
    }

    private function handleFacebook(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;
        $object = $payload['object'] ?? '';

        if ($object !== 'page') {
            return true;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            $account = \App\Models\SocialAccount::where('platform', 'facebook')
                ->where('platform_account_id', $pageId)
                ->first();

            if (! $account) {
                Log::warning('Facebook webhook: account not found', ['page_id' => $pageId]);
                continue;
            }

            foreach ($changes as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                match ($field) {
                    'feed' => $this->createInboxMessage($account, 'facebook', $value['item'] ?? '', $value),
                    'comments' => $this->createInboxMessage($account, 'facebook', 'comment', $value),
                    'mentions' => $this->createInboxMessage($account, 'facebook', 'mention', $value),
                    default => null,
                };
            }
        }

        return true;
    }

    private function handleInstagram(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;
        $object = $payload['object'] ?? '';

        if ($object !== 'instagram') {
            return true;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $igUserId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            $account = \App\Models\SocialAccount::where('platform', 'instagram')
                ->where('platform_account_id', $igUserId)
                ->first();

            if (! $account) {
                continue;
            }

            foreach ($changes as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                match ($field) {
                    'comments' => $this->createInboxMessage($account, 'instagram', 'comment', $value),
                    'mentions' => $this->createInboxMessage($account, 'instagram', 'mention', $value),
                    default => null,
                };
            }
        }

        return true;
    }

    private function handleLinkedIn(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;

        foreach ($payload['events'] ?? [] as $event) {
            $type = $event['type'] ?? '';
            $actor = $event['actor'] ?? '';

            $account = \App\Models\SocialAccount::where('platform', 'linkedin')
                ->where('platform_account_id', $actor)
                ->first();

            if (! $account) {
                continue;
            }

            match ($type) {
                'COMMENT_CREATED', 'COMMENT_UPDATED' => $this->createInboxMessage($account, 'linkedin', 'comment', $event),
                default => null,
            };
        }

        return true;
    }

    private function handleTikTok(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;

        foreach ($payload['data']['events'] ?? [] as $event) {
            $eventType = $event['event'] ?? '';

            match ($eventType) {
                'video.comment.create' => $this->handleTikTokEvent($account ?? null, 'tiktok', 'comment', $event),
                'video.mention.create' => $this->handleTikTokEvent($account ?? null, 'tiktok', 'mention', $event),
                default => null,
            };
        }

        return true;
    }

    private function handleYouTube(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;

        $videoId = (string) ($payload['entry']['id'] ?? '');
        $channelId = (string) ($payload['entry']['author']['uri'] ?? '');

        if ($videoId && $channelId) {
            $account = \App\Models\SocialAccount::where('platform', 'youtube')
                ->where('platform_account_id', $channelId)
                ->first();

            if (! $account) {
                Log::warning('YouTube webhook: account not found', ['channel_id' => $channelId]);
            }
        }

        return true;
    }

    private function handleTelegram(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;

        try {
            $telegram = app(\App\Services\Telegram\TelegramBotService::class);
            $telegram->handleWebhook($payload);
            return true;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Telegram webhook handling failed: {$e->getMessage()}");
        }
    }

    private function handleWorkflow(WebhookProcessingLog $log): bool
    {
        $payload = $log->payload;
        $workflowId = $payload['workflow_id'] ?? null;

        if (! $workflowId) {
            throw new \RuntimeException('Missing workflow_id in payload');
        }

        $workflow = \App\Models\Workflow::find($workflowId);

        if (! $workflow) {
            throw new \RuntimeException("Workflow not found: {$workflowId}");
        }

        if ($workflow->status !== 'active') {
            throw new \RuntimeException("Workflow is not active: {$workflowId}");
        }

        $engine = app(\App\Services\Workflow\WorkflowEngine::class);
        $engine->execute($workflow, $payload);

        return true;
    }

    private function handleGeneric(WebhookProcessingLog $log): bool
    {
        Log::info('Processing generic webhook', [
            'webhook_id' => $log->webhook_id,
            'platform' => $log->platform,
        ]);
        return true;
    }

    private function createInboxMessage($account, string $platform, string $type, array $value): void
    {
        $content = match ($type) {
            'comment' => $value['message'] ?? $value['text'] ?? $value['commentText'] ?? null,
            'mention' => $value['message'] ?? $value['text'] ?? null,
            default => null,
        };

        if (! $content) {
            return;
        }

        $authorName = $value['from']['name'] ?? $value['from']['username'] ?? $value['authorName'] ?? 'Unknown';
        $messageId = $value['comment_id'] ?? $value['id'] ?? $value['post_id'] ?? $value['platform_message_id'] ?? null;

        $account->inboxMessages()->create([
            'platform' => $platform,
            'platform_message_id' => $messageId,
            'message_type' => $type,
            'author_name' => $authorName,
            'content' => $content,
            'status' => 'unread',
            'received_at' => now(),
        ]);
    }

    private function handleTikTokEvent(?\App\Models\SocialAccount $account, string $platform, string $type, array $event): void
    {
        if (! $account) {
            return;
        }

        $this->createInboxMessage($account, $platform, $type, $event);
    }

    /**
     * Handle job failure - move to dead letter queue.
     */
    public function failed(\Throwable $exception): void
    {
        $log = WebhookProcessingLog::find($this->webhookLogId);

        if ($log && ! $log->isDeadLetter()) {
            $log->markAsDeadLetter("Job failed after {$this->attempts()} attempts: {$exception->getMessage()}");
            Log::critical("Webhook job failed permanently", [
                'webhook_log_id' => $this->webhookLogId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Get unique ID for deduplication.
     */
    public function uniqueId(): string
    {
        return "process_webhook_{$this->webhookLogId}";
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        $log = WebhookProcessingLog::find($this->webhookLogId);
        return ['webhook', $log->platform ?? 'unknown', "attempt:{$log->attempt}"];
    }
}
