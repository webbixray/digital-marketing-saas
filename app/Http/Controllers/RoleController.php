<?php

namespace App\Http\Controllers;

use App\Services\RBAC\EnterpriseRBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private EnterpriseRBACService $rbacService;

    public function __construct(EnterpriseRBACService $rbacService)
    {
        $this->rbacService = $rbacService;
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $roles = Role::where('agency_id', $agencyId)
            ->orWhereNull('agency_id')
            ->with('permissions')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        $permissions = Permission::all();

        return view('roles.create', compact('permissions', 'agencyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $agencyId = $request->user()->agency_id;

        $this->rbacService->createCustomRole(
            $agencyId,
            $validated['name'],
            $validated['permissions'] ?? []
        );

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Request $request, Role $role): View
    {
        $agencyId = $request->user()->agency_id;
        $permissions = Permission::all();
        $rolePermissions = $this->rbacService->getRolePermissions($role->id);

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'agencyId'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $agencyId = $request->user()->agency_id;

        $success = $this->rbacService->updateRole($agencyId, $role->id, $validated);

        if (! $success) {
            return redirect()->back()->with('error', 'Role not found or access denied.');
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $success = $this->rbacService->deleteRole($agencyId, $role->id);

        if (! $success) {
            return redirect()->back()->with('error', 'Role not found or access denied.');
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $success = $this->rbacService->assignRoleToUser(
            $validated['user_id'],
            $validated['role_id']
        );

        if (! $success) {
            return redirect()->back()->with('error', 'Failed to assign role.');
        }

        return redirect()->back()->with('success', 'Role assigned successfully.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $success = $this->rbacService->removeRoleFromUser(
            $validated['user_id'],
            $validated['role_id']
        );

        if (! $success) {
            return redirect()->back()->with('error', 'Failed to remove role.');
        }

        return redirect()->back()->with('success', 'Role removed successfully.');
    }

    public function auditTrail(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        $filters = $request->only(['action', 'user_id', 'date_from', 'date_to']);

        $auditLogs = $this->rbacService->getAuditTrail($agencyId, $filters);

        return view('roles.audit', compact('auditLogs'));
    }
}
