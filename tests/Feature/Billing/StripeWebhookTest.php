<?php

namespace Tests\Feature\Billing;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        $agency = Agency::factory()->create([
            'customer_id' => 'cus_test123',
            'subscription_id' => 'sub_test123',
        ]);

        $user = User::factory()->create(['agency_id' => $agency->id]);

        // Test with invalid signature
        $response = $this->postJson('/billing/webhook', [], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        // Returns 400 or 401 (both are valid rejection responses)
        $this->assertTrue(in_array($response->status(), [400, 401]));
    }

    public function test_stripe_subscription_exists(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_id' => 'sub_test123',
            'customer_id' => 'cus_test123',
        ]);

        $this->assertEquals('pro', $agency->fresh()->subscription_plan);
    }

    public function test_stripe_webhook_route_exists(): void
    {
        $response = $this->postJson('/billing/webhook', [], ['Stripe-Signature' => 'test']);
        $this->assertNotEquals(404, $response->status());
    }

    public function test_stripe_gateway_has_webhook_handler(): void
    {
        $this->assertTrue(method_exists(\App\Services\Billing\StripeGateway::class, 'handleWebhook'));
    }
}
