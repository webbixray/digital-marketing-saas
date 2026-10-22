<?php

namespace App\Services\RBAC;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class EnterpriseRBACService
{
    /**
     * Create a custom role for an agency with specific permissions.
     */
    /**
     * @param  array<int, string>  $permissions
     */
    public function createCustomRole(int $agencyId, string $name, array $permissions): Role
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => 'web',
            'agency_id' => $agencyId,
        ]);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        $this->logAuditTrail($agencyId, 'role.created', "Role '{$name}' created", null, Role::class, $role->id, [
            'permissions' => $permissions,
        ]);

        return $role;
    }

    /**
     * Update an existing role.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRole(int $agencyId, int $roleId, array $data): bool
    {
        $role = $this->findRoleForAgency($agencyId, $roleId);

        if (! $role) {
            return false;
        }

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }

        $role->save();

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $this->logAuditTrail($agencyId, 'role.updated', "Role '{$role->name}' updated", null, Role::class, $role->id, $data);

        return true;
    }

    /**
     * Delete a role from an agency.
     */
    public function deleteRole(int $agencyId, int $roleId): bool
    {
        $role = $this->findRoleForAgency($agencyId, $roleId);

        if (! $role) {
            return false;
        }

        $roleName = $role->name;

        // Detach all permissions before deleting
        $role->syncPermissions([]);
        $role->delete();

        $this->logAuditTrail($agencyId, 'role.deleted', "Role '{$roleName}' deleted", null);

        return true;
    }

    /**
     * Assign a role to a user.
     */
    public function assignRoleToUser(int $userId, int $roleId): bool
    {
        $user = User::find($userId);
        $role = Role::find($roleId);

        if (! $user || ! $role) {
            return false;
        }

        // Ensure the role belongs to the user's agency
        if ($role->agency_id !== null && $role->agency_id !== $user->agency_id) {
            return false;
        }

        $user->assignRole($role);

        $this->logAuditTrail($user->agency_id, 'role.assigned', "Role '{$role->name}' assigned to user {$user->id}", $user);

        return true;
    }

    /**
     * Remove a role from a user.
     */
    public function removeRoleFromUser(int $userId, int $roleId): bool
    {
        $user = User::find($userId);
        $role = Role::find($roleId);

        if (! $user || ! $role) {
            return false;
        }

        // Ensure the role belongs to the user's agency
        if ($role->agency_id !== null && $role->agency_id !== $user->agency_id) {
            return false;
        }

        $user->removeRole($role);

        $this->logAuditTrail($user->agency_id, 'role.removed', "Role '{$role->name}' removed from user {$user->id}", $user);

        return true;
    }

    /**
     * Get all permissions for a user.
     */
    /**
     * @return array<int, string>
     */
    public function getUserPermissions(int $user): array
    {
        $user = is_int($user) ? User::find($user) : $user;

        if (! $user) {
            return [];
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Get all permissions for a role.
     */
    /**
     * @return array<int, string>
     */
    public function getRolePermissions(int $roleId): array
    {
        $role = Role::find($roleId);

        if (! $role) {
            return [];
        }

        return $role->permissions()->pluck('name')->toArray();
    }

    /**
     * Enforce field-level permissions for a user on a resource.
     */
    public function enforceFieldLevelPermissions(User $user, string $resource, string $action): bool
    {
        // Owner and admin bypass field-level checks
        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        $permissionName = "field.{$resource}.{$action}";

        return $user->hasPermissionTo($permissionName)
            || $user->hasPermissionTo("field.{$resource}.*")
            || $user->hasRole('manager');
    }

    /**
     * Get audit trail for an agency with optional filters.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAuditTrail(int $agencyId, array $filters = []): Collection
    {
        $query = ActivityLog::where('agency_id', $agencyId);

        if (isset($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        return $query->recent($filters['limit'] ?? 50)->get();
    }

    /**
     * Find a role scoped to an agency.
     */
    private function findRoleForAgency(int $agencyId, int $roleId): ?Role
    {
        return Role::where('id', $roleId)
            ->where(function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId)
                    ->orWhereNull('agency_id');
            })
            ->first();
    }

    /**
     * Log an audit trail entry.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    private function logAuditTrail(int $agencyId, string $action, string $description, ?User $user = null, ?string $subjectType = null, ?int $subjectId = null, array $metadata = []): void
    {
        ActivityLog::log(
            Agency::find($agencyId) ?? new Agency(['id' => $agencyId]),
            $action,
            $description,
            $user,
            $subjectType,
            $subjectId,
            $metadata
        );
    }
}
