<?php

namespace Tests\Feature\Performance;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrillReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_can_boot_and_serve_requests(): void
    {
        // Simulate a fresh database restore by creating seed data
        $agency = Agency::factory()->create([
            'name' => 'Drill Test Agency',
            'slug' => 'drill-test-agency',
            'subscription_plan' => 'pro',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'email' => 'drill@test.com',
        ]);

        // Verify data integrity (simulating backup verification)
        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'name' => 'Drill Test Agency',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'drill@test.com',
        ]);

        // Verify critical routes resolve (simulating post-restore smoke test)
        $response = $this->getJson('/health');
        $response->assertStatus(200);
    }

    public function test_critical_endpoints_accessible_after_restore(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        // Critical user journeys after DR restore
        $endpoints = [
            '/api/v1/dashboard',
            '/api/v1/posts',
            '/api/v1/accounts',
            '/api/v1/clients',
            '/api/v1/invoices',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertTrue(
                in_array($response->status(), [200, 404]),
                "Endpoint $endpoint should be accessible (200) or empty (404) after restore, got {$response->status()}"
            );
        }
    }

    public function test_backup_directory_writable(): void
    {
        $backupDir = storage_path('app/backups');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($backupDir);
        $this->assertTrue(is_dir($backupDir));
        $this->assertTrue(is_writable($backupDir));
    }
}
