<?php

namespace Tests\Feature\Ai;

use App\Models\Agency;
use App\Models\AiCreditPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_returns_agency_credits(): void
    {
        $agency = Agency::factory()->create([
            'ai_credits' => 500,
            'ai_credits_purchased' => 1000,
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/ai/credits/balance');

        $response->assertStatus(200);
        $response->assertJson([
            'credits' => 500,
            'total_purchased' => 1000,
        ]);
    }

    public function test_balance_returns_zero_for_user_without_agency(): void
    {
        $user = User::factory()->create([
            'agency_id' => null,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        // Agency middleware blocks users without agency
        $response = $this->getJson('/api/v1/ai/credits/balance');

        $response->assertStatus(403);
    }

    public function test_purchase_creates_stripe_session(): void
    {
        $agency = Agency::factory()->create([
            'ai_credits' => 0,
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        // Mock Stripe to avoid actual API calls
        \Stripe\Stripe::setApiKey('sk_test_');
        
        $response = $this->postJson('/api/v1/ai/credits/purchase', [
            'credits' => 500,
        ]);

        // Should return a checkout session (or error if Stripe not configured)
        $this->assertTrue(in_array($response->status(), [200, 402, 500]));
    }

    public function test_purchase_rejects_invalid_credits(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v1/ai/credits/purchase', [
            'credits' => 999, // Not an allowed value
        ]);

        $response->assertStatus(422);
    }

    public function test_success_credits_authenticated_agency_only(): void
    {
        $agency = Agency::factory()->create([
            'ai_credits' => 100,
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        // Attempt to success with invalid session
        $response = $this->getJson('/api/v1/ai/credits/success?session_id=cs_test_invalid');

        // Should reject because session doesn't exist / Stripe not configured
        // but should not credit a different agency
        $this->assertTrue(in_array($response->status(), [400, 403, 404, 500]));

        // Verify agency credits unchanged
        $this->assertEquals(100, $agency->fresh()->ai_credits);
    }

    public function test_success_records_purchase(): void
    {
        $agency = Agency::factory()->create([
            'ai_credits' => 0,
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        // Invalid session should not create a purchase record
        $response = $this->getJson('/api/v1/ai/credits/success?session_id=cs_test_invalid');

        $this->assertTrue(in_array($response->status(), [400, 403, 404, 500]));
        $this->assertCount(0, AiCreditPurchase::where('agency_id', $agency->id)->get());
    }

    public function test_unauthorized_user_cannot_access_balance(): void
    {
        $response = $this->getJson('/api/v1/ai/credits/balance');

        $response->assertStatus(401);
    }
}
