<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencySettingsTest extends TestCase
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

    // ==================== Settings ====================

    public function test_settings_requires_authentication(): void
    {
        $response = $this->get(route('agency.settings'));
        $response->assertRedirect(route('login'));
    }

    public function test_settings_requires_agency_access(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $response = $this->actingAs($user)->get(route('agency.settings'));
        $response->assertForbidden();
    }

    public function test_settings_displays_agency_data(): void
    {
        $response = $this->actingAs($this->owner)->get(route('agency.settings'));

        $response->assertOk();
        $response->assertViewIs('agency.settings');
        $response->assertViewHas('agency', function ($agency) {
            return $agency->id === $this->agency->id;
        });
    }

    public function test_update_settings_requires_authentication(): void
    {
        $response = $this->put(route('agency.settings.update'), [
            'name' => 'Updated Name',
            'email' => 'test@test.com',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_update_settings_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), []);

        $response->assertSessionHasErrors(['name', 'email', 'timezone', 'currency']);
    }

    public function test_update_settings_validates_email_format(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), [
            'name' => 'Test Agency',
            'email' => 'invalid-email',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_update_settings_validates_currency_length(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), [
            'name' => 'Test Agency',
            'email' => 'test@test.com',
            'timezone' => 'UTC',
            'currency' => 'US',
        ]);

        $response->assertSessionHasErrors('currency');
    }

    public function test_update_settings_succeeds_with_valid_data(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), [
            'name' => 'Updated Agency Name',
            'email' => 'updated@agency.com',
            'timezone' => 'America/New_York',
            'currency' => 'EUR',
        ]);

        $response->assertRedirect(route('agency.settings'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('agencies', [
            'id' => $this->agency->id,
            'name' => 'Updated Agency Name',
            'email' => 'updated@agency.com',
            'timezone' => 'America/New_York',
            'currency' => 'EUR',
        ]);
    }

    // ==================== Team ====================

    public function test_team_requires_authentication(): void
    {
        $response = $this->get(route('agency.team'));
        $response->assertRedirect(route('login'));
    }

    public function test_team_displays_members(): void
    {
        User::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->owner)->get(route('agency.team'));

        $response->assertOk();
        $response->assertViewIs('agency.team');
        $response->assertViewHas('members');
    }

    public function test_team_only_shows_agency_members(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->owner)->get(route('agency.team'));

        $response->assertOk();
        $members = $response->viewData('members');
        $this->assertFalse($members->contains('id', $otherUser->id));
    }

    // ==================== Invite Member ====================

    public function test_invite_member_requires_authentication(): void
    {
        $response = $this->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'new@test.com',
            'role' => 'member',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_invite_member_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), []);

        $response->assertSessionHasErrors(['email', 'name', 'role']);
    }

    public function test_invite_member_validates_email_format(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'invalid-email',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_invite_member_validates_email_uniqueness(): void
    {
        User::factory()->create(['email' => 'existing@test.com']);

        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'existing@test.com',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_invite_member_validates_role(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'new@test.com',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_invite_member_succeeds_with_valid_data(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'newmember@test.com',
            'role' => 'member',
        ]);

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'newmember@test.com',
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);
    }

    public function test_invite_member_assigns_to_current_agency(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'newmember@test.com',
            'role' => 'admin',
        ]);

        $newUser = User::where('email', 'newmember@test.com')->first();
        $this->assertEquals($this->agency->id, $newUser->agency_id);
    }

    // ==================== Update Member Role ====================

    public function test_update_member_role_requires_authentication(): void
    {
        $response = $this->put(route('agency.team.role', $this->member), [
            'role' => 'admin',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_update_member_role_validates_role_field(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.team.role', $this->member), [
            'role' => 'invalid-role',
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_update_member_role_succeeds_for_owner(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.team.role', $this->member), [
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'role' => 'admin',
        ]);
    }

    public function test_update_member_role_succeeds_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->put(route('agency.team.role', $this->member), [
            'role' => 'manager',
        ]);

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'role' => 'manager',
        ]);
    }

    public function test_update_member_role_fails_for_regular_member(): void
    {
        $response = $this->actingAs($this->member)->put(route('agency.team.role', $this->admin), [
            'role' => 'member',
        ]);

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('error');
    }

    public function test_update_member_role_requires_valid_member_id(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->owner)->put(route('agency.team.role', $otherUser), [
            'role' => 'admin',
        ]);

        $response->assertForbidden();
    }

    // ==================== Remove Member ====================

    public function test_remove_member_requires_authentication(): void
    {
        $response = $this->delete(route('agency.team.remove', $this->member));
        $response->assertRedirect(route('login'));
    }

    public function test_remove_member_succeeds_for_owner(): void
    {
        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $this->member));

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $this->member->id]);
    }

    public function test_remove_member_succeeds_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('agency.team.remove', $this->member));

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $this->member->id]);
    }

    public function test_remove_member_fails_for_regular_member(): void
    {
        $response = $this->actingAs($this->member)->delete(route('agency.team.remove', $this->admin));

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'deleted_at' => null]);
    }

    public function test_remove_member_prevents_self_removal(): void
    {
        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $this->owner));

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->owner->id, 'deleted_at' => null]);
    }

    public function test_remove_member_prevents_removing_owner(): void
    {
        $ownerUser = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('agency.team.remove', $ownerUser));

        $response->assertRedirect(route('agency.team'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $ownerUser->id, 'deleted_at' => null]);
    }

    public function test_remove_member_prevents_removing_from_other_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $otherUser));

        $response->assertForbidden();
    }

    // ==================== Billing ====================

    public function test_billing_requires_authentication(): void
    {
        $response = $this->get('/agency/billing');
        $response->assertRedirect(route('login'));
    }

    public function test_billing_displays_plans_and_invoices(): void
    {
        $response = $this->actingAs($this->owner)->get('/agency/billing');

        $response->assertOk();
        $response->assertViewIs('agency.billing');
        $response->assertViewHas(['agency', 'plans', 'currentPlan']);
    }

    // ==================== Upgrade ====================

    public function test_upgrade_requires_authentication(): void
    {
        $response = $this->post(route('agency.billing.upgrade'), [
            'plan' => 'pro',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_upgrade_validates_plan_field(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.billing.upgrade'), [
            'plan' => 'invalid-plan',
        ]);

        $response->assertSessionHasErrors('plan');
    }

    public function test_upgrade_validates_required_plan(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.billing.upgrade'), []);

        $response->assertSessionHasErrors('plan');
    }

    public function test_upgrade_succeeds_with_valid_plan(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.billing.upgrade'), [
            'plan' => 'pro',
        ]);

        $response->assertRedirect(route('agency.billing'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('agencies', [
            'id' => $this->agency->id,
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
        ]);
    }

    public function test_upgrade_sets_subscription_dates(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.billing.upgrade'), [
            'plan' => 'enterprise',
        ]);

        $this->agency->refresh();
        $this->assertNotNull($this->agency->subscription_start);
        $this->assertNotNull($this->agency->subscription_end);
    }

    public function test_upgrade_accepts_starter_plan(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.billing.upgrade'), [
            'plan' => 'starter',
        ]);

        $response->assertRedirect(route('agency.billing'));
        $this->assertDatabaseHas('agencies', [
            'id' => $this->agency->id,
            'subscription_plan' => 'starter',
        ]);
    }

    // ==================== Subscribe & CancelSubscription via BillingController ====================
    // Note: AgencyController::subscribe and cancelSubscription methods exist but are not
    // registered as web routes. They may be intended for future API routes.
    // The billing cancel-subscription route delegates to BillingController instead.
}
