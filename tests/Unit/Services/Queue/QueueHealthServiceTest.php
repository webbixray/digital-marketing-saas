<?php

namespace Tests\Unit\Services\Queue;

use Tests\TestCase;

class QueueHealthServiceTest extends TestCase
{
    public function test_get_health_check_returns_structure(): void
    {
        $service = new \App\Services\Queue\QueueHealthService();
        $result = $service->getHealthCheck();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('healthy', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('issues', $result);
    }

    public function test_get_status_returns_driver(): void
    {
        $service = new \App\Services\Queue\QueueHealthService();
        $result = $service->getStatus();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('driver', $result);
        $this->assertArrayHasKey('configured', $result);
    }

    public function test_get_failed_jobs_count_returns_zero_or_more(): void
    {
        $service = new \App\Services\Queue\QueueHealthService();
        $result = $service->getFailedJobsCount();
        
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
