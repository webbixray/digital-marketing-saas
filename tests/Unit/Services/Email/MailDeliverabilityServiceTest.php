<?php

namespace Tests\Unit\Services\Email;

use App\Services\Email\MailDeliverabilityService;
use Tests\TestCase;

class MailDeliverabilityServiceTest extends TestCase
{
    public function test_is_valid_email_returns_true_for_valid_email(): void
    {
        $service = new MailDeliverabilityService;
        $this->assertTrue($service->isValidEmail('test@example.com'));
    }

    public function test_is_valid_email_returns_false_for_invalid_email(): void
    {
        $service = new MailDeliverabilityService;
        $this->assertFalse($service->isValidEmail('invalid-email'));
    }

    public function test_get_health_check_returns_array(): void
    {
        $service = new MailDeliverabilityService;
        $result = $service->getHealthCheck();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('healthy', $result);
        $this->assertArrayHasKey('issues', $result);
    }

    public function test_get_status_returns_array(): void
    {
        $service = new MailDeliverabilityService;
        $result = $service->getStatus();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('mailer', $result);
        $this->assertArrayHasKey('configured', $result);
    }
}
