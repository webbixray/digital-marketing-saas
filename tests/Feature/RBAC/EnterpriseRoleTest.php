<?php

namespace Tests\Feature\RBAC;

use App\Models\Agency;
use App\Models\User;
use App\Services\RBAC\EnterpriseRBACService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnterpriseRoleTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private EnterpriseRBACService $rbacService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->admin = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
        ]);
        $this->rbacService = app(EnterpriseRBACService::class);
    }

    public function test_it_creates_custom_role_with_permissions(): void
    {
        $permission = Permission::create([
            'name' => 'campaigns.create',
            'guard_name' => 'web',
        ]);

        $role = $this->rbacService->createCustomRole(
            $this->agency->id,
            'Campaign Manager',
            ['campaigns.create']
        );

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Campaign Manager', $role->name);
        $this->assertEquals($this->agency->id, $role->agency_id);
        $this->assertTrue($role->hasPermissionTo('campaigns.create'));
    }

    public function test_it_assigns_and_removes_role_from_user(): void
    {
        $role = $this->rbacService->createCustomRole(
            $this->agency->id,
            'Editor',
            []
        );

        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $assignResult = $this->rbacService->assignRoleToUser($member->id, $role->id);
        $this->assertTrue($assignResult);
        $this->assertTrue($member->fresh()->hasRole('Editor'));

        $removeResult = $this->rbacService->removeRoleFromUser($member->id, $role->id);
        $this->assertTrue($removeResult);
        $this->assertFalse($member->fresh()->hasRole('Editor'));
    }

    public function test_it_enforces_field_level_permissions(): void
    {
        $permission = Permission::create([
            'name' => 'field.campaigns.edit',
            'guard_name' => 'web',
        ]);

        $role = $this->rbacService->createCustomRole(
            $this->agency->id,
            'Field Editor',
            ['field.campaigns.edit']
        );

        $member = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'member',
        ]);

        $this->rbacService->assignRoleToUser($member->id, $role->id);

        $canEdit = $this->rbacService->enforceFieldLevelPermissions(
            $member->fresh(),
            'campaigns',
            'edit'
        );
        $this->assertTrue($canEdit);

        $canDelete = $this->rbacService->enforceFieldLevelPermissions(
            $member->fresh(),
            'campaigns',
            'delete'
        );
        $this->assertFalse($canDelete);
    }

    public function test_it_retrieves_audit_trail_for_agency(): void
    {
        $this->rbacService->createCustomRole(
            $this->agency->id,
            'Test Role',
            []
        );

        $auditTrail = $this->rbacService->getAuditTrail($this->agency->id);

        $this->assertNotEmpty($auditTrail);
        $this->assertEquals('role.created', $auditTrail->first()->action);
    }
}
