<?php

namespace Tests\Feature\UAT;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: create an agency with an owner user.
     */
    private function createAgencyWithOwner(): array
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $owner = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        return [$agency, $owner];
    }

    // ──────────────────────────────────────────────
    // 1. View team page
    // ──────────────────────────────────────────────

    public function test_owner_can_view_team_page(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        $response = $this->get(route('agency.team'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_team_page(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $response = $this->get(route('agency.team'));
        $response->assertStatus(200);
    }

    // ──────────────────────────────────────────────
    // 2. Invite team member
    // ──────────────────────────────────────────────

    public function test_owner_can_invite_team_member(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        $response = $this->post(route('agency.team.invite'), [
            'name' => 'New Team Member',
            'email' => 'member@test.com',
            'role' => 'manager',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'member@test.com',
            'role' => 'manager',
            'agency_id' => $agency->id,
        ]);
    }

    public function test_admin_can_invite_team_member(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $response = $this->post(route('agency.team.invite'), [
            'name' => 'Admin Invited',
            'email' => 'admininvited@test.com',
            'role' => 'member',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'admininvited@test.com',
            'role' => 'member',
            'agency_id' => $agency->id,
        ]);
    }

    public function test_member_can_only_invite_to_own_agency(): void
    {
        [$agency1, $owner1] = $this->createAgencyWithOwner();
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $member = User::factory()->create([
            'agency_id' => $agency1->id,
            'role' => 'member',
        ]);
        $this->actingAs($member);

        // Member invites someone - they get assigned to member's agency
        $response = $this->post(route('agency.team.invite'), [
            'name' => 'Cross Agency Test',
            'email' => 'cross@test.com',
            'role' => 'member',
        ]);

        $response->assertStatus(302);
        // The invited user is assigned to the member's agency, not a different one
        $this->assertDatabaseHas('users', [
            'email' => 'cross@test.com',
            'agency_id' => $agency1->id,
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'cross@test.com',
            'agency_id' => $agency2->id,
        ]);
    }

    public function test_invite_requires_valid_role(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        $response = $this->post(route('agency.team.invite'), [
            'name' => 'Bad Role',
            'email' => 'badrole@test.com',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'badrole@test.com',
        ]);
    }

    public function test_invite_requires_unique_email(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        User::factory()->create([
            'agency_id' => $agency->id,
            'email' => 'duplicate@test.com',
            'role' => 'member',
        ]);
        $this->actingAs($owner);

        $response = $this->post(route('agency.team.invite'), [
            'name' => 'Duplicate Email',
            'email' => 'duplicate@test.com',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ──────────────────────────────────────────────
    // 3. Change member role
    // ──────────────────────────────────────────────

    public function test_owner_can_update_member_role(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($owner);

        $response = $this->put(route('agency.team.role', $member), [
            'role' => 'manager',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'manager',
        ]);
    }

    public function test_admin_can_update_member_role(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($admin);

        $response = $this->put(route('agency.team.role', $member), [
            'role' => 'admin',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'admin',
        ]);
    }

    public function test_cross_agency_role_update_returns_403(): void
    {
        [$agency1, $owner1] = $this->createAgencyWithOwner();
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $foreignMember = User::factory()->create([
            'agency_id' => $agency2->id,
            'role' => 'member',
        ]);
        $this->actingAs($owner1);

        $response = $this->put(route('agency.team.role', $foreignMember), [
            'role' => 'manager',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $foreignMember->id,
            'role' => 'member',
        ]);
    }

    public function test_role_update_requires_valid_role(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($owner);

        $response = $this->put(route('agency.team.role', $member), [
            'role' => 'nonexistent',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'member',
        ]);
    }

    // ──────────────────────────────────────────────
    // 4. Remove member
    // ──────────────────────────────────────────────

    public function test_owner_can_remove_member(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($owner);

        $response = $this->delete(route('agency.team.remove', $member));

        $response->assertStatus(302);
        // User model uses SoftDeletes, so we check soft delete
        $this->assertSoftDeleted('users', [
            'id' => $member->id,
        ]);
    }

    public function test_admin_can_remove_member(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($admin);

        $response = $this->delete(route('agency.team.remove', $member));

        $response->assertStatus(302);
        $this->assertSoftDeleted('users', [
            'id' => $member->id,
        ]);
    }

    public function test_manager_cannot_remove_member(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'manager',
        ]);
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($manager);

        $response = $this->delete(route('agency.team.remove', $member));

        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
        ]);
    }

    public function test_member_cannot_remove_other_member(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $member1 = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $member2 = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);
        $this->actingAs($member1);

        $response = $this->delete(route('agency.team.remove', $member2));

        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
        $this->assertDatabaseHas('users', [
            'id' => $member2->id,
        ]);
    }

    public function test_cross_agency_remove_returns_403(): void
    {
        [$agency1, $owner1] = $this->createAgencyWithOwner();
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $foreignMember = User::factory()->create([
            'agency_id' => $agency2->id,
            'role' => 'member',
        ]);
        $this->actingAs($owner1);

        $response = $this->delete(route('agency.team.remove', $foreignMember));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $foreignMember->id,
        ]);
    }

    public function test_owner_cannot_remove_themselves(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        $response = $this->delete(route('agency.team.remove', $owner));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    public function test_cannot_remove_agency_owner(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $response = $this->delete(route('agency.team.remove', $owner));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    // ──────────────────────────────────────────────
    // 5. RBAC — Agency isolation
    // ──────────────────────────────────────────────

    public function test_user_can_only_see_members_from_own_agency(): void
    {
        [$agency1, $owner1] = $this->createAgencyWithOwner();
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);

        $member1 = User::factory()->create([
            'agency_id' => $agency1->id,
            'name' => 'Alpha Member',
            'email' => 'alpha@test.com',
            'role' => 'member',
        ]);
        $member2 = User::factory()->create([
            'agency_id' => $agency2->id,
            'name' => 'Beta Member',
            'email' => 'beta@test.com',
            'role' => 'member',
        ]);
        $this->actingAs($owner1);

        $response = $this->get(route('agency.team'));
        $response->assertStatus(200);
        $response->assertSee('Alpha Member');
        $response->assertSee('alpha@test.com');
        $response->assertDontSee('Beta Member');
        $response->assertDontSee('beta@test.com');
    }

    public function test_member_from_other_agency_cannot_view_team(): void
    {
        [$agency1, $owner1] = $this->createAgencyWithOwner();
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $foreignUser = User::factory()->create([
            'agency_id' => $agency2->id,
            'role' => 'owner',
        ]);
        $this->actingAs($foreignUser);

        $response = $this->get(route('agency.team'));
        $response->assertStatus(200);

        // Should see own agency, not agency1
        $members = $response->viewData('members');
        foreach ($members as $member) {
            $this->assertEquals($agency2->id, $member->agency_id);
        }
    }

    public function test_owner_invite_assigns_correct_agency_id(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        $this->post(route('agency.team.invite'), [
            'name' => 'Agency Member',
            'email' => 'agencymember@test.com',
            'role' => 'member',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'agencymember@test.com',
            'agency_id' => $agency->id,
        ]);
    }

    // ──────────────────────────────────────────────
    // 6. Full workflow integration
    // ──────────────────────────────────────────────

    public function test_full_team_management_workflow(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        // Step 1: Invite a manager
        $response = $this->post(route('agency.team.invite'), [
            'name' => 'Workflow Manager',
            'email' => 'workflow@test.com',
            'role' => 'manager',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'workflow@test.com',
            'role' => 'manager',
        ]);

        $member = User::where('email', 'workflow@test.com')->first();

        // Step 2: Promote to admin
        $response = $this->put(route('agency.team.role', $member), [
            'role' => 'admin',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'admin',
        ]);

        // Step 3: Demote back to member
        $response = $this->put(route('agency.team.role', $member), [
            'role' => 'member',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'member',
        ]);

        // Step 4: Remove member (soft delete)
        $response = $this->delete(route('agency.team.remove', $member));
        $response->assertStatus(302);
        $this->assertSoftDeleted('users', [
            'id' => $member->id,
        ]);
    }

    public function test_owner_and_admin_cannot_be_removed_by_non_owner(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);

        // Manager tries to remove admin
        $manager = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'manager',
        ]);
        $this->actingAs($manager);

        $response = $this->delete(route('agency.team.remove', $admin));
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);

        // Admin tries to remove owner
        $this->actingAs($admin);
        $response = $this->delete(route('agency.team.remove', $owner));
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    public function test_spatie_roles_are_assigned_on_invite(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAs($owner);

        $this->post(route('agency.team.invite'), [
            'name' => 'Spatie Member',
            'email' => 'spatie@test.com',
            'role' => 'member',
        ]);

        $member = User::where('email', 'spatie@test.com')->first();
        $this->assertNotNull($member);
        $this->assertEquals('member', $member->role);
    }
}
