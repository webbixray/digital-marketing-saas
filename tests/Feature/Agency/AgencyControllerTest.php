<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->owner = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    public function test_settings_requires_auth(): void
    {
        $response = $this->get(route('agency.settings'));
        $response->assertRedirect(route('login'));
    }

    public function test_settings_shows_agency_data(): void
    {
        $response = $this->actingAs($this->owner)->get(route('agency.settings'));

        $response->assertOk();
        $response->assertViewHas('agency');
    }

    public function test_update_settings_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), []);

        $response->assertSessionHasErrors(['agency_name', 'email', 'timezone', 'currency']);
    }

    public function test_update_settings_updates_agency(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), [
            'agency_name' => 'Updated Agency Name',
            'website' => 'https://example.com',
            'description' => 'Test description',
            'email' => 'updated@example.com',
            'timezone' => 'UTC',
            'currency' => 'EUR',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('agencies', [
            'id' => $this->agency->id,
            'name' => 'Updated Agency Name',
            'currency' => 'EUR',
        ]);
    }

    public function test_team_page_lists_members(): void
    {
        User::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->owner)->get(route('agency.team'));

        $response->assertOk();
        $members = $response->viewData('members');
        $this->assertCount(4, $members); // 3 members + owner
    }

    public function test_invite_member_creates_new_user(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'email' => 'newmember@example.com',
            'name' => 'New Member',
            'role' => 'member',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'agency_id' => $this->agency->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);
    }

    public function test_invite_member_validates_email(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_invite_member_prevents_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'email' => 'duplicate@example.com',
            'name' => 'Duplicate User',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_update_member_role_works_for_owner(): void
    {
        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)->put(route('agency.team.role', $member), [
            'role' => 'admin',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'admin',
        ]);
    }

    public function test_update_member_role_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherMember = User::factory()->create([
            'agency_id' => $otherAgency->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)->put(route('agency.team.role', $otherMember), [
            'role' => 'admin',
        ]);

        $response->assertForbidden();
    }

    public function test_remove_member_deletes_user(): void
    {
        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $member));

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $member->id]);
    }

    public function test_remove_member_prevents_removing_owner(): void
    {
        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $this->owner));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }
}
