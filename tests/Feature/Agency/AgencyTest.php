<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyTest extends TestCase
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

    public function test_it_shows_settings(): void
    {
        $response = $this->actingAs($this->owner)->get(route('agency.settings'));

        $response->assertOk();
        $response->assertViewIs('agency.settings');
    }

    public function test_it_updates_settings(): void
    {
        $response = $this->actingAs($this->owner)->put(route('agency.settings.update'), [
            'agency_name' => 'Updated Agency',
            'email' => 'updated@agency.com',
            'timezone' => 'America/New_York',
            'currency' => 'EUR',
        ]);

        $response->assertRedirect(route('agency.settings'));
        $this->assertDatabaseHas('agencies', [
            'id' => $this->agency->id,
            'name' => 'Updated Agency',
        ]);
    }

    public function test_it_shows_team(): void
    {
        User::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->owner)->get(route('agency.team'));

        $response->assertOk();
        $response->assertViewIs('agency.team');
        $response->assertViewHas('members');
    }

    public function test_it_invites_team_member(): void
    {
        $response = $this->actingAs($this->owner)->post(route('agency.team.invite'), [
            'name' => 'New Member',
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'member@example.com',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_it_updates_member_role(): void
    {
        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)->put(route('agency.team.role', $member), [
            'role' => 'manager',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => 'manager',
        ]);
    }

    public function test_it_removes_member(): void
    {
        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $member));

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $member->id]);
    }

    public function test_it_prevents_self_removal(): void
    {
        $response = $this->actingAs($this->owner)->delete(route('agency.team.remove', $this->owner));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('agency.settings'));

        $response->assertRedirect(route('login'));
    }
}
