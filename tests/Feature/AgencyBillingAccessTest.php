<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UAT-02 regression (2026-10-24): agency billing/invoices and agency settings
 * writes were reachable by ANY authenticated team member (viewers included),
 * exposing invoice data to junior roles on both API and web surfaces.
 * Owner/admin-only access is required.
 */
class AgencyBillingAccessTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $owner;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'subscription_status' => 'active',
        ]);
        $this->owner = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->admin = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
        ]);
        $this->member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);
    }

    // ---- API surface (/api/v1/agency/*) ----

    public function test_api_billing_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/agency/billing');
        $response->assertStatus(401);
    }

    public function test_api_member_cannot_view_billing(): void
    {
        $response = $this->actingAs($this->member)->getJson('/api/v1/agency/billing');
        $response->assertStatus(403);
    }

    public function test_api_admin_can_view_billing(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/agency/billing');
        $response->assertStatus(200);
    }

    public function test_api_owner_can_view_billing(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/agency/billing');
        $response->assertStatus(200);
    }

    public function test_api_member_cannot_update_agency_settings(): void
    {
        $response = $this->actingAs($this->member)->putJson('/api/v1/agency/settings', []);
        $response->assertStatus(403);
    }

    public function test_api_owner_can_update_agency_settings(): void
    {
        $response = $this->actingAs($this->owner)->putJson('/api/v1/agency/settings', [
            'timezone' => 'UTC',
        ]);
        $response->assertStatus(200);
    }

    // ---- Web surface (/agency/*) ----

    public function test_web_member_cannot_view_billing(): void
    {
        $response = $this->actingAs($this->member)->get('/agency/billing');
        $response->assertStatus(403);
    }

    public function test_web_owner_can_view_billing(): void
    {
        $response = $this->actingAs($this->owner)->get('/agency/billing');
        $response->assertStatus(200);
    }
}
