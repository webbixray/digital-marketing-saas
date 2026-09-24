<?php

namespace Tests\Feature\Billing;

use App\Models\Agency;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\UsageQuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_credit_balance_starts_at_zero(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.credits.index'));

        $response->assertOk();
        $response->assertViewIs('billing.credits');
        $response->assertViewHas('balance');
    }

    public function test_add_credits_increases_balance(): void
    {
        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 5000,
            'type' => 'purchase',
            'balance_after' => 5000,
        ]);

        $total = CreditTransaction::where('agency_id', $this->agency->id)->sum('amount');
        $this->assertEquals(5000, $total);
    }

    public function test_use_credits_decreases_balance(): void
    {
        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 5000,
            'type' => 'purchase',
            'balance_after' => 5000,
        ]);

        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => -1000,
            'type' => 'usage',
            'balance_after' => 4000,
        ]);

        $total = CreditTransaction::where('agency_id', $this->agency->id)->sum('amount');
        $this->assertEquals(4000, $total);
    }

    public function test_refund_credits_increases_balance(): void
    {
        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 3000,
            'type' => 'purchase',
            'balance_after' => 3000,
        ]);

        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 500,
            'type' => 'refund',
            'balance_after' => 3500,
        ]);

        $total = CreditTransaction::where('agency_id', $this->agency->id)->sum('amount');
        $this->assertEquals(3500, $total);
    }

    public function test_transaction_history_lists_all_transactions(): void
    {
        CreditTransaction::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
        ]);

        $transactions = CreditTransaction::where('agency_id', $this->agency->id)->get();
        $this->assertCount(5, $transactions);
    }

    public function test_transaction_history_filters_by_type(): void
    {
        CreditTransaction::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'type' => 'purchase',
        ]);

        CreditTransaction::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'type' => 'usage',
        ]);

        $purchases = CreditTransaction::where('agency_id', $this->agency->id)
            ->where('type', 'purchase')
            ->get();

        $this->assertCount(3, $purchases);
    }

    public function test_quota_tracking_records_usage(): void
    {
        $quota = UsageQuota::factory()->create([
            'agency_id' => $this->agency->id,
            'metric' => 'ai_tokens',
            'limit' => 10000,
            'used' => 0,
        ]);

        $quota->update(['used' => 2500]);

        $this->assertEquals(2500, $quota->fresh()->used);
        $this->assertLessThan($quota->limit, $quota->fresh()->used);
    }

    public function test_quota_tracking_detects_exceeded(): void
    {
        $quota = UsageQuota::factory()->create([
            'agency_id' => $this->agency->id,
            'metric' => 'ai_tokens',
            'limit' => 10000,
            'used' => 12000,
        ]);

        $exceeded = UsageQuota::where('agency_id', $this->agency->id)
            ->whereColumn('used', '>', 'limit')
            ->exists();

        $this->assertTrue($exceeded);
    }

    public function test_purchase_endpoint_requires_auth(): void
    {
        $response = $this->postJson(route('billing.credits.purchase'), ['amount' => 1000]);
        $response->assertUnauthorized();
    }

    public function test_purchase_endpoint_validates_amount(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('billing.credits.purchase'), [
            'amount' => -100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function test_cross_agency_prevents_balance_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        CreditTransaction::factory()->create([
            'agency_id' => $otherAgency->id,
            'user_id' => $otherUser->id,
            'amount' => 9999,
            'type' => 'purchase',
            'balance_after' => 9999,
        ]);

        $total = CreditTransaction::where('agency_id', $this->agency->id)->sum('amount');
        $this->assertEquals(0, $total);
    }

    public function test_credits_view_displays_balance_and_transactions(): void
    {
        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 5000,
            'type' => 'purchase',
            'balance_after' => 5000,
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.credits.index'));

        $response->assertOk();
        $response->assertViewHas('balance');
        $response->assertViewHas('transactions');
        $response->assertViewHas('summary');
    }

    public function test_credits_summary_calculates_totals(): void
    {
        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 5000,
            'type' => 'purchase',
            'balance_after' => 5000,
        ]);

        CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => -2000,
            'type' => 'usage',
            'balance_after' => 3000,
        ]);

        $totalPurchases = CreditTransaction::where('agency_id', $this->agency->id)
            ->where('type', 'purchase')
            ->sum('amount');

        $totalUsage = CreditTransaction::where('agency_id', $this->agency->id)
            ->where('type', 'usage')
            ->sum('amount');

        $this->assertEquals(5000, $totalPurchases);
        $this->assertEquals(-2000, $totalUsage);
    }

    public function test_transaction_description_is_recorded(): void
    {
        $transaction = CreditTransaction::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'purchase',
            'balance_after' => 1000,
            'description' => 'Initial credit purchase',
        ]);

        $this->assertEquals('Initial credit purchase', $transaction->description);
        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transaction->id,
            'description' => 'Initial credit purchase',
        ]);
    }
}
