<?php

namespace Tests\Feature\Team;

use App\Models\Agency;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
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
            'role' => 'admin',
        ]);
    }

    public function test_it_lists_teams_for_agency(): void
    {
        Team::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('teams.index'));

        $response->assertOk();
        $response->assertViewIs('teams.index');
        $response->assertViewHas('teams');
    }

    public function test_it_creates_a_team(): void
    {
        $response = $this->actingAs($this->owner)->post(route('teams.store'), [
            'name' => 'Marketing Team',
            'description' => 'Marketing team description',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teams', [
            'name' => 'Marketing Team',
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);
    }

    public function test_it_requires_name_when_creating_team(): void
    {
        $response = $this->actingAs($this->owner)->post(route('teams.store'), [
            'description' => 'Missing name',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_it_shows_team_details(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('teams.show', $team));

        $response->assertOk();
        $response->assertViewIs('teams.show');
    }

    public function test_it_updates_a_team(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put(route('teams.update', $team), [
            'name' => 'Updated Team Name',
            'description' => 'Updated description',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Team Name',
            'is_active' => false,
        ]);
    }

    public function test_it_deletes_a_team(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('teams.destroy', $team));

        $response->assertRedirect();
        $this->assertDatabaseMissing('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_it_prevents_cross_agency_team_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $team = Team::factory()->create([
            'agency_id' => $otherAgency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('teams.show', $team));

        $response->assertForbidden();
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->get(route('teams.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_it_sends_team_invitation(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->post(route('teams.invite', $team), [
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);
    }

    public function test_it_validates_invitation_email(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->post(route('teams.invite', $team), [
            'email' => 'invalid-email',
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_it_accepts_invitation(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $invitee = User::factory()->create([
            'agency_id' => $this->agency->id,
            'email' => 'invitee@example.com',
        ]);

        $response = $this->actingAs($invitee)->get(route('teams.invite.accept', $invitation->token));

        $response->assertRedirect();
        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_it_cancels_invitation(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('teams.invite.cancel', $invitation->token));

        $response->assertRedirect();
        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_it_shows_create_team_form(): void
    {
        $response = $this->actingAs($this->owner)->get(route('teams.create'));

        $response->assertOk();
        $response->assertViewIs('teams.create');
    }

    public function test_it_shows_edit_team_form(): void
    {
        $team = Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('teams.edit', $team));

        $response->assertOk();
        $response->assertViewIs('teams.edit');
    }

    public function test_it_filters_active_teams(): void
    {
        Team::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
            'is_active' => true,
        ]);

        Team::factory()->create([
            'agency_id' => $this->agency->id,
            'owner_id' => $this->owner->id,
            'is_active' => false,
        ]);

        $activeTeams = Team::byAgency($this->agency->id)->active()->get();

        $this->assertCount(2, $activeTeams);
    }

    public function test_it_validates_team_name_max_length(): void
    {
        $response = $this->actingAs($this->owner)->post(route('teams.store'), [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors('name');
    }
}
