<?php

namespace App\Http\Controllers;

use App\Models\PermissionCategory;
use App\Services\RBAC\PermissionMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionMatrixController extends Controller
{
    private PermissionMatrixService $matrixService;

    public function __construct(PermissionMatrixService $matrixService)
    {
        $this->matrixService = $matrixService;
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show the permission matrix (roles × permissions grouped by category).
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        $matrixData = $this->matrixService->getMatrix($agencyId);

        return view('roles.permissions-matrix', $matrixData);
    }

    /**
     * Bulk update permissions for a role via JSON.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $agencyId = $request->user()->agency_id;

        $role = Role::where('id', $validated['role_id'])
            ->where(function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId)
                    ->orWhereNull('agency_id');
            })
            ->first();

        if (! $role) {
            return redirect()->back()->with('error', 'Role not found or access denied.');
        }

        // Prevent modification of system roles by non-owners
        if ($role->is_system && ! $request->user()->isOwner()) {
            return redirect()->back()->with('error', 'System roles can only be modified by the owner.');
        }

        $this->matrixService->syncRolePermissions(
            $role,
            $validated['permissions'] ?? []
        );

        Log::info('Permission matrix updated', [
            'role_id' => $role->id,
            'agency_id' => $agencyId,
            'permissions_count' => count($validated['permissions'] ?? []),
        ]);

        return redirect()->route('roles.matrix')
            ->with('success', "Permissions for '{$role->name}' updated successfully.");
    }

    /**
     * Show current user's permissions.
     */
    public function myPermissions(Request $request): View
    {
        $user = $request->user();
        $permissions = $this->matrixService->getUserPermissions($user);
        $categories = PermissionCategory::orderBy('sort_order')->get();

        // Group permissions by category
        $grouped = [];
        foreach ($categories as $category) {
            $grouped[$category->slug] = [
                'category' => $category,
                'permissions' => [],
            ];
        }

        foreach ($permissions as $permName) {
            $matched = false;
            foreach ($categories as $category) {
                if (str_contains($permName, $category->slug)) {
                    $grouped[$category->slug]['permissions'][] = $permName;
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                $grouped['uncategorized'] = $grouped['uncategorized'] ?? [
                    'category' => null,
                    'permissions' => [],
                ];
                $grouped['uncategorized']['permissions'][] = $permName;
            }
        }

        return view('roles.my-permissions', compact('user', 'grouped', 'permissions'));
    }

    /**
     * API endpoint for frontend permission checks.
     */
    public function checkPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission' => 'required|string',
        ]);

        $user = $request->user();
        $hasPermission = $this->matrixService->hasPermission($user, $validated['permission']);

        return response()->json([
            'permission' => $validated['permission'],
            'granted' => $hasPermission,
        ]);
    }
}
