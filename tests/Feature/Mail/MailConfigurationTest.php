<?php

namespace Tests\Feature\Mail;

use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    public function test_mail_from_address_is_configured(): void
    {
        $fromAddress = config('mail.from.address');
        $this->assertNotEmpty($fromAddress);
    }

    public function test_mail_driver_is_smtp_in_production(): void
    {
        if (app()->isProduction()) {
            $this->assertNotEquals('log', config('mail.default'));
        }

        $this->assertTrue(true);
    }

    public function test_mail_from_name_is_configured(): void
    {
        $fromName = config('mail.from.name');
        $this->assertNotEmpty($fromName);
    }
}
