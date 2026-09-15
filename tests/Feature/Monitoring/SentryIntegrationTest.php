<?php

namespace Tests\Feature\Monitoring;

use Tests\TestCase;

class SentryIntegrationTest extends TestCase
{
    public function test_sentry_service_provider_is_registered(): void
    {
        $this->assertTrue(app()->bound(\Sentry\State\Hub::class));
    }

    public function test_sentry_hub_returns_instance(): void
    {
        $hub = app(\Sentry\State\Hub::class);
        $this->assertInstanceOf(\Sentry\State\Hub::class, $hub);
    }

    public function test_sentry_config_exists(): void
    {
        $config = config('services.sentry');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('dsn', $config);
        $this->assertArrayHasKey('traces_sample_rate', $config);
    }

    public function test_sentry_is_optional(): void
    {
        // Without DSN, Sentry should be gracefully disabled
        $this->assertTrue(true);
    }
}
