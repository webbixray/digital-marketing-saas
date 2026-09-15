<?php

namespace Tests\Feature\Queue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_is_configured(): void
    {
        $queueConnection = config('queue.default');
        $this->assertNotEmpty($queueConnection);
    }

    public function test_redis_queue_is_available(): void
    {
        if (config('queue.default') === 'redis') {
            $this->assertTrue(extension_loaded('redis') || class_exists('Predis\Client'));
        }

        $this->assertTrue(true);
    }

    public function test_jobs_can_be_dispatched(): void
    {
        Queue::fake();

        // Test that a job can be dispatched
        \App\Jobs\Email\SendEmailCampaign::dispatch(1);

        Queue::assertPushed(\App\Jobs\Email\SendEmailCampaign::class);
    }

    public function test_failed_jobs_table_exists(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')
        );
    }
}
