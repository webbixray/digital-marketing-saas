<?php

namespace Tests\Feature\RBAC;

use App\Models\Agency;
use App\Models\User;
use App\Services\RBAC\EnterpriseRBACService;
use App\Services\RBAC\PermissionMatrixService;
use Database\Seeders\PermissionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Agency $otherAgency;

    private User $owner;

    private User $admin;

    private User $manager;

    private PermissionMatrixService $matrixService;

    private EnterpriseRBACService $rbacService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(PermissionCategorySeeder::class);

        $this->matrixService = app(PermissionMatrixService::class);
        $this->rbacService = app(EnterpriseRBACService::class);

        $this->agency = Agency::factory()->create();
        $this->otherAgency = Agency::factory()->create();

        $this->owner = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);

        $this->admin = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
        ]);

        $this->manager = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'manager',
        ]);
    }

    /** Test 1: Permission matrix page loads successfully for authenticated users */
    public function test_matrix_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('roles.matrix'));

        $response->assertStatus(200);
        $response->assertViewIs('roles.permissions-matrix');
        $response->assertViewHas('roles');
        $response->assertViewHas('categories');
    }

    /** Test 2: Unauthenticated users are redirected */
    public function test_matrix_requires_authentication(): void
    {
        $response = $this->get(route('roles.matrix'));
        $response->assertRedirect('/login');
    }

    /** Test 3: Owner can update role permissions via matrix */
    public function test_matrix_update_permissions(): void
    {
        $this->actingAs($this->owner);

        $role = Role::where('name', 'manager')->first();
        $permissions = Permission::pluck('name')->take(3)->toArray();

        $response = $this->putJson(route('roles.matrix.update'), [
            'role_id' => $role->id,
            'permissions' => $permissions,
        ]);

        $response->assertStatus(302); // Redirect back
        $this->assertDatabaseHas('role_has_permissions', [
            'role_id' => $role->id,
        ]);
    }

    /** Test 4: Update requires valid role_id and permissions */
    public function test_matrix_update_validates_input(): void
    {
        $this->actingAs($this->owner);

        $response = $this->putJson(route('roles.matrix.update'), [
            'role_id' => 99999,
            'permissions' => ['invalid-permission-name'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role_id']);
    }

    /** Test 5: Cross-agency isolation — cannot modify roles from another agency */
    public function test_cross_agency_isolation(): void
    {
        $this->actingAs($this->owner);

        // Create a role in the other agency
        $otherRole = $this->rbacService->createCustomRole($this->otherAgency->id, 'Other Role', []);

        // Attempt to update from first agency
        $response = $this->putJson(route('roles.matrix.update'), [
            'role_id' => $otherRole->id,
            'permissions' => ['view campaigns'],
        ]);

        // Should redirect with error (role not found for this agency)
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /** Test 6: Service getMatrix returns proper structure */
    public function test_matrix_service_returns_correct_structure(): void
    {
        $matrix = $this->matrixService->getMatrix($this->agency->id);

        $this->assertArrayHasKey('roles', $matrix);
        $this->assertArrayHasKey('categories', $matrix);
        $this->assertArrayHasKey('matrix', $matrix);
        $this->assertNotEmpty($matrix['roles']);
        $this->assertNotEmpty($matrix['categories']);
    }

    /** Test 7: Service syncRolePermissions bulk syncs */
    public function test_sync_role_permissions(): void
    {
        $role = Role::where('name', 'member')->first();
        $permissions = ['view campaigns', 'create posts'];

        $updatedRole = $this->matrixService->syncRolePermissions($role, $permissions);

        $this->assertCount(2, $updatedRole->permissions);
        $this->assertEquals('view campaigns', $updatedRole->permissions->first()->name);
    }

    /** Test 8: getUserPermissions returns flattened list */
    public function test_get_user_permissions_returns_flattened_list(): void
    {
        $role = Role::where('name', 'member')->first();
        $this->manager->assignRole($role);

        $permissions = $this->matrixService->getUserPermissions($this->manager);

        $this->assertIsArray($permissions);
        $this->assertNotEmpty($permissions);
        $this->assertContainsOnly('string', $permissions);
    }

    /** Test 9: hasPermission respects agency isolation */
    public function test_has_permission_with_agency_isolation(): void
    {
        // Owner always has permission
        $this->assertTrue(
            $this->matrixService->hasPermission($this->owner, 'any-permission')
        );

        // Manager needs the permission assigned
        $role = Role::where('name', 'manager')->first();
        $permission = Permission::where('name', 'view campaigns')->first();
        $role->givePermissionTo($permission);
        $this->manager->assignRole($role);

        $this->assertTrue(
            $this->matrixService->hasPermission($this->manager, 'view campaigns')
        );

        $this->assertFalse(
            $this->matrixService->hasPermission($this->manager, 'delete agencies')
        );
    }

    /** Test 10: API check-permission endpoint returns correct JSON */
    public function test_api_check_permission_endpoint(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson(route('api.check-permission'), [
            'permission' => 'view campaigns',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'permission' => 'view campaigns',
            'granted' => true,
        ]);
    }

    /** Test 11: Permission categories seeder creates 10 categories */
    public function test_permission_category_seeder_creates_10_categories(): void
    {
        $this->seed(PermissionCategorySeeder::class);

        $this->assertDatabaseCount('permission_categories', 10);

        $expectedSlugs = [
            'content', 'campaigns', 'clients', 'analytics',
            'billing', 'team', 'settings', 'integrations',
            'reports', 'ai',
        ];

        foreach ($expectedSlugs as $slug) {
            $this->assertDatabaseHas('permission_categories', ['slug' => $slug]);
        }
    }

    /** Test 12: System roles are protected from modification by non-owners */
    public function test_system_roles_protected_from_non_owners(): void
    {
        // Mark owner as system role
        $ownerRole = Role::where('name', 'owner')->first();
        $ownerRole->is_system = true;
        $ownerRole->save();

        $this->actingAs($this->admin);

        $response = $this->putJson(route('roles.matrix.update'), [
            'role_id' => $ownerRole->id,
            'permissions' => ['view campaigns'],
        ]);

        // Admin should not be able to modify system role
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }
}
