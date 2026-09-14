<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();

        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'member', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_admin_dashboard_requires_auth(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(302);
    }

    public function test_admin_dashboard_returns_200_for_owner(): void
    {
        $this->user->assignRole('owner');
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    public function test_admin_dashboard_returns_200_for_admin(): void
    {
        $this->user->assignRole('admin');
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    public function test_admin_dashboard_redirects_for_non_admin(): void
    {
        $this->user->assignRole('member');
        $response = $this->actingAs($this->user)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_admin_health_page_returns_200(): void
    {
        $this->user->assignRole('owner');
        $response = $this->actingAs($this->user)->get(route('admin.health'));
        $response->assertStatus(200);
    }

    public function test_admin_failed_jobs_page_returns_200(): void
    {
        $this->user->assignRole('owner');
        $response = $this->actingAs($this->user)->get(route('admin.failed-jobs'));
        $response->assertStatus(200);
    }

    public function test_admin_retry_job_returns_redirect(): void
    {
        $this->user->assignRole('owner');
        $response = $this->actingAs($this->user)->post(route('admin.retry-job', ['jobId' => 1]));
        $response->assertStatus(302);
    }

    public function test_admin_delete_failed_job_returns_redirect(): void
    {
        $this->user->assignRole('owner');
        $response = $this->actingAs($this->user)->delete(route('admin.delete-failed-job', ['jobId' => 1]));
        $response->assertStatus(302);
    }
}
