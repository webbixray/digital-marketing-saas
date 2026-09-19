<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Agency management
            'view agencies',
            'create agencies',
            'update agencies',
            'delete agencies',

            // Social accounts
            'view social accounts',
            'create social accounts',
            'update social accounts',
            'delete social accounts',

            // Campaigns
            'view campaigns',
            'create campaigns',
            'update campaigns',
            'delete campaigns',

            // Clients
            'view clients',
            'create clients',
            'update clients',
            'delete clients',

            // Posts
            'view posts',
            'create posts',
            'update posts',
            'delete posts',
            'publish posts',
            'schedule posts',

            // Analytics
            'view analytics',
            'export reports',

            // Team management
            'view team',
            'invite team',
            'remove team',
            'update team roles',

            // Billing
            'view invoices',
            'create invoices',
            'update invoices',
            'delete invoices',
            'manage subscription',

            // AI Content
            'generate ai content',

            // Workflows
            'view workflows',
            'create workflows',
            'update workflows',
            'delete workflows',

            // White Label
            'manage white label',

            // Custom Fields
            'view custom fields',
            'create custom fields',
            'update custom fields',
            'delete custom fields',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles with permissions
        $roles = [
            'owner' => $permissions, // All permissions
            'admin' => array_diff($permissions, ['delete agencies', 'manage subscription']),
            'manager' => array_filter($permissions, fn($p) => in_array($p, [
                'view agencies', 'view social accounts', 'create social accounts', 'update social accounts',
                'view campaigns', 'create campaigns', 'update campaigns',
                'view clients', 'create clients', 'update clients',
                'view posts', 'create posts', 'update posts', 'publish posts', 'schedule posts',
                'view analytics', 'export reports',
                'view team', 'invite team',
                'view invoices',
                'generate ai content',
                'view workflows', 'create workflows', 'update workflows',
                'view custom fields', 'create custom fields', 'update custom fields',
            ])),
            'member' => array_filter($permissions, fn($p) => in_array($p, [
                'view agencies', 'view social accounts',
                'view campaigns',
                'view clients',
                'view posts', 'create posts',
                'view analytics',
                'generate ai content',
                'view workflows',
                'view custom fields',
            ])),
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
