<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Agency;
use App\Services\Billing\StripeDunningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeDunningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_active_subscription_returns_false_without_subscription_id(): void
    {
        $agency = Agency::factory()->create([
            'subscription_id' => null,
            'customer_id' => 'cus_test123',
        ]);

        $service = new StripeDunningService;
        $result = $service->hasActiveSubscription($agency);

        $this->assertFalse($result);
    }

    public function test_has_active_subscription_returns_false_without_customer_id(): void
    {
        $agency = Agency::factory()->create([
            'subscription_id' => null,
            'customer_id' => null,
        ]);

        $service = new StripeDunningService;
        $result = $service->hasActiveSubscription($agency);

        $this->assertFalse($result);
    }

    public function test_get_upcoming_invoice_returns_null_without_subscription(): void
    {
        $agency = Agency::factory()->create([
            'subscription_id' => null,
        ]);

        $service = new StripeDunningService;
        $result = $service->getUpcomingInvoice($agency);

        $this->assertNull($result);
    }
}
