<?php

namespace Tests\Feature\Admin;

use App\Models\Agency;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $owner;
    private User $admin;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();

        // Create roles
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'member', 'guard_name' => 'web']);
        Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->owner = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->owner->assignRole('owner');

        $this->admin = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'manager',
        ]);
        $this->manager->assignRole('manager');
    }

    public function test_admin_dashboard_requires_auth(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_dashboard_allows_owner(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $response->assertOk();
    }

    public function test_admin_dashboard_allows_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
    }

    public function test_admin_dashboard_denies_member_role(): void
    {
        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);
        $member->assignRole('member');

        $response = $this->actingAs($member)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_dashboard_denies_manager_role(): void
    {
        $response = $this->actingAs($this->manager)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_dashboard_shows_stats(): void
    {
        SocialPost::factory()->count(5)->create(['agency_id' => $this->agency->id, 'status' => 'published']);
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agency->id, 'status' => 'failed']);

        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_agencies', $stats);
        $this->assertArrayHasKey('total_social_posts', $stats);
        $this->assertArrayHasKey('failed_posts', $stats);
    }

    public function test_admin_dashboard_shows_recent_activity(): void
    {
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('recentActivity');
    }

    public function test_health_endpoint_returns_health_data(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.health'));
        $response->assertOk();
        $response->assertViewHas('health');
    }

    public function test_failed_jobs_endpoint_lists_jobs(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.failed-jobs'));
        $response->assertOk();
        $response->assertViewHas('failedJobs');
    }
}
