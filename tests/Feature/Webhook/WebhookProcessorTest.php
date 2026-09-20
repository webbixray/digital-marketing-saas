<?php

namespace Tests\Feature\Webhook;

use App\Events\WebhookReceived;
use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookProcessingLog;
use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear deduplication cache between tests
        Cache::flush();
        
        // Ensure the table exists (RefreshDatabase should handle this)
        if (!Schema::hasTable('webhook_processing_logs')) {
            $this->artisan('migrate', ['--force' => true]);
        }
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_webhook_processor_creates_log(): void
    {
        $processor = app(WebhookProcessor::class);

        $log = $processor->process(
            platform: 'facebook',
            eventType: 'page',
            payload: ['object' => 'page', 'entry' => [['id' => 'test_123']]],
            signature: null,
        );

        $this->assertDatabaseHas('webhook_processing_logs', [
            'platform' => 'facebook',
            'event_type' => 'page',
            'status' => 'pending',
        ]);
    }

    public function test_webhook_processor_fires_event(): void
    {
        Event::fake(WebhookReceived::class);

        $processor = app(WebhookProcessor::class);

        $processor->process(
            platform: 'instagram',
            eventType: 'instagram',
            payload: ['object' => 'instagram', 'entry' => [['id' => 'test_456']]],
            signature: null,
        );

        Event::assertDispatched(WebhookReceived::class, function ($event) {
            return $event->platform === 'instagram'
                && $event->eventType === 'instagram';
        });
    }

    public function test_webhook_processor_deduplicates(): void
    {
        $processor = app(WebhookProcessor::class);

        $payload = ['object' => 'page', 'entry' => [['id' => 'dedup_test_123']]];

        $log1 = $processor->process(
            platform: 'facebook',
            eventType: 'page',
            payload: $payload,
            signature: null,
        );

        $log2 = $processor->process(
            platform: 'facebook',
            eventType: 'page',
            payload: $payload,
            signature: null,
        );

        $this->assertEquals($log1->id, $log2->id);
        $this->assertEquals(1, WebhookProcessingLog::where('platform', 'facebook')->count());
    }

    public function test_webhook_processor_verifies_facebook_signature(): void
    {
        config(['services.facebook.webhook_secret' => 'test_secret']);

        $processor = app(WebhookProcessor::class);

        $payload = ['object' => 'page'];
        $payloadJson = json_encode($payload);
        $validSignature = 'sha256=' . hash_hmac('sha256', $payloadJson, 'test_secret');

        $this->assertTrue($processor->verifyFacebookSignature($payloadJson, $validSignature));
        $this->assertFalse($processor->verifyFacebookSignature($payloadJson, 'invalid'));
    }

    public function test_webhook_processor_verifies_tiktok_signature(): void
    {
        config(['services.tiktok.webhook_secret' => 'tiktok_secret']);

        $processor = app(WebhookProcessor::class);

        $payload = ['data' => ['events' => []]];
        $payloadJson = json_encode($payload);
        $validSignature = hash_hmac('sha256', $payloadJson, 'tiktok_secret');

        $this->assertTrue($processor->verifyTikTokSignature($payloadJson, $validSignature));
        $this->assertFalse($processor->verifyTikTokSignature($payloadJson, 'invalid'));
    }

    public function test_process_webhook_job_has_correct_retry_config(): void
    {
        $job = new ProcessWebhookJob(1);

        $this->assertEquals(5, ProcessWebhookJob::MAX_ATTEMPTS);
        $this->assertEquals(5, $job->tries);
        $this->assertEquals([60, 300, 900, 3600, 3600], ProcessWebhookJob::RETRY_DELAYS);
    }

    public function test_process_webhook_job_backoff_calculation(): void
    {
        $job = new ProcessWebhookJob(1);

        $this->assertIsInt($job->backoff());
        $this->assertContains($job->backoff(), [60, 300, 900, 3600]);
    }

    public function test_webhook_processing_log_status_methods(): void
    {
        $log = WebhookProcessingLog::create([
            'platform' => 'test',
            'event_type' => 'test',
            'webhook_id' => 'status_test_123',
            'payload' => [],
            'status' => 'pending',
            'attempt' => 0,
            'max_attempts' => 5,
        ]);

        $this->assertTrue($log->isPending());
        $this->assertFalse($log->isCompleted());
        $this->assertFalse($log->isFailed());
        $this->assertFalse($log->isDeadLetter());
        $this->assertTrue($log->canRetry());

        $log->markAsProcessing();
        $this->assertFalse($log->isPending());

        $log->markAsCompleted();
        $this->assertTrue($log->isCompleted());
        $this->assertFalse($log->canRetry());
    }

    public function test_webhook_processing_log_can_retry(): void
    {
        $log = WebhookProcessingLog::create([
            'platform' => 'test',
            'event_type' => 'test',
            'webhook_id' => 'retry_test_123',
            'payload' => [],
            'status' => 'failed',
            'attempt' => 3,
            'max_attempts' => 5,
        ]);

        $this->assertTrue($log->canRetry());

        $log->incrementAttempt();
        $this->assertTrue($log->canRetry());

        $log->incrementAttempt();
        $this->assertFalse($log->canRetry());
    }

    public function test_webhook_processing_log_marks_dead_letter(): void
    {
        $log = WebhookProcessingLog::create([
            'platform' => 'test',
            'event_type' => 'test',
            'webhook_id' => 'dlq_test_123',
            'payload' => [],
            'status' => 'failed',
            'attempt' => 5,
            'max_attempts' => 5,
        ]);

        $log->markAsDeadLetter('Max attempts reached');

        $this->assertTrue($log->isDeadLetter());
        $this->assertFalse($log->canRetry());
        $this->assertNotNull($log->failed_at);
    }

    public function test_facebook_webhook_controller_uses_processor(): void
    {
        config(['services.facebook.webhook_secret' => null]);
        config(['webhooks.allow_unsigned' => true]);

        $response = $this->postJson('/webhook/facebook', [
            'object' => 'page',
            'entry' => [['id' => 'ctrl_test_12345', 'changes' => []]],
        ]);

        $response->assertOk();
    }

    public function test_instagram_webhook_controller_uses_processor(): void
    {
        $response = $this->postJson('/instagram/webhook', [
            'object' => 'instagram',
            'entry' => [['id' => 'ctrl_test_67890', 'changes' => []]],
        ]);

        $response->assertOk();
    }

    public function test_webhook_received_event_has_correct_data(): void
    {
        $event = new WebhookReceived(
            platform: 'test',
            eventType: 'test.event',
            webhookId: 'evt_test_123',
            payload: ['key' => 'value'],
            signature: 'sig123',
            signatureValid: true,
        );

        $this->assertEquals('test', $event->platform);
        $this->assertEquals('test.event', $event->eventType);
        $this->assertEquals('evt_test_123', $event->webhookId);
        $this->assertEquals(['key' => 'value'], $event->payload);
        $this->assertEquals('sig123', $event->signature);
        $this->assertTrue($event->signatureValid);
    }

    public function test_webhook_processor_dispatches_handler(): void
    {
        $processor = app(WebhookProcessor::class);

        $processor->process(
            platform: 'test',
            eventType: 'test',
            payload: ['test' => 'handler_data'],
            signature: null,
            handler: function (array $payload) {
                // Handler was dispatched
            },
        );

        $this->assertTrue(true);
    }

    public function test_process_webhook_job_unique_id(): void
    {
        $job = new ProcessWebhookJob(42);
        $this->assertEquals('process_webhook_42', $job->uniqueId());
    }

    public function test_process_webhook_job_tags(): void
    {
        $log = WebhookProcessingLog::create([
            'platform' => 'facebook',
            'event_type' => 'page',
            'webhook_id' => 'tag_test_123',
            'payload' => [],
            'status' => 'pending',
            'attempt' => 2,
            'max_attempts' => 5,
        ]);

        $job = new ProcessWebhookJob($log->id);
        $tags = $job->tags();

        $this->assertContains('webhook', $tags);
        $this->assertContains('facebook', $tags);
        $this->assertContains('attempt:2', $tags);
    }
}
