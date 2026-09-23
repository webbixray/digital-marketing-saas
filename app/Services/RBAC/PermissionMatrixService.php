<?php

namespace App\Services\RBAC;

use App\Models\PermissionCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionMatrixService
{
    /**
     * Get the permission matrix for an agency.
     *
     * Returns roles with their permissions grouped by category.
     *
     * @return array{
     *     roles: Collection<Role>,
     *     categories: Collection<PermissionCategory>,
     *     matrix: array<string, array<string, bool>>
     * }
     */
    public function getMatrix(int $agencyId): array
    {
        $roles = Role::where('agency_id', $agencyId)
            ->orWhereNull('agency_id')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $categories = PermissionCategory::orderBy('sort_order')->get();

        $matrix = [];
        foreach ($roles as $role) {
            $rolePermissionNames = $role->permissions->pluck('name')->toArray();
            $matrix[$role->id] = [
                'role' => $role,
                'permissions' => [],
            ];

            foreach ($categories as $category) {
                $categoryPermissions = $this->getPermissionsForCategory($category);
                $matrix[$role->id]['permissions'][$category->slug] = [];

                foreach ($categoryPermissions as $permission) {
                    $matrix[$role->id]['permissions'][$category->slug][$permission->name] = in_array(
                        $permission->name,
                        $rolePermissionNames,
                        true
                    );
                }
            }
        }

        return [
            'roles' => $roles,
            'categories' => $categories,
            'matrix' => $matrix,
        ];
    }

    /**
     * Sync permissions for a role (bulk update).
     */
    public function syncRolePermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->load('permissions');
    }

    /**
     * Get flattened list of all permissions for a user.
     *
     * @return list<string>
     */
    public function getUserPermissions(User $user): array
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Check if user has a specific permission (with agency isolation).
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Owner and admin bypass all
        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }

    /**
     * Get all permissions belonging to a category (by slug pattern).
     *
     * @return Collection<Permission>
     */
    private function getPermissionsForCategory(PermissionCategory $category): Collection
    {
        // Map category slugs to permission name patterns
        $slug = $category->slug;

        return Permission::where('name', 'LIKE', "%{$slug}%")
            ->orWhere('name', 'LIKE', "%{$slug}%")
            ->orderBy('name')
            ->get();
    }
}
