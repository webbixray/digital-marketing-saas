@extends('layouts.unified')
@section('title', 'Permission Matrix')

@section('content')
<x-flash-messages />

<div class="space-y-6" x-data="permissionMatrix()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Permission Matrix
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Manage role permissions across all categories. Toggle changes and save.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button
                @click="saveAll()"
                :disabled="saving || !hasChanges"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <i class="fas fa-save"></i>
                <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
            </button>
            <a
                href="{{ route('roles.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

    <!-- Status Banner -->
    <div
        x-show="statusMessage"
        x-transition
        :class="statusType === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800'"
        class="px-4 py-3 rounded-lg border flex items-center justify-between"
    >
        <span x-text="statusMessage"></span>
        <button @click="statusMessage = ''" class="text-current opacity-50 hover:opacity-100">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Category Sections -->
    <div class="space-y-6">
        @forelse($categories as $category)
            @php
                // Collect all permissions for this category from any role
                $categoryPermissions = collect();
                foreach ($roles as $role) {
                    foreach ($role->permissions as $perm) {
                        if (str_contains($perm->name, $category->slug)) {
                            $categoryPermissions->push($perm);
                        }
                    }
                }
                $categoryPermissions = $categoryPermissions->unique('name')->sortBy('name')->values();
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Category Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between cursor-pointer"
                     @click="toggleCategory('{{ $category->slug }}')">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                            <i class="fas {{ $category->icon ?? 'fa-tag' }} text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                            @if($category->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $categoryPermissions->count() }} permissions</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform"
                           :class="expandedCategories['{{ $category->slug }}'] ? 'rotate-180' : ''"></i>
                    </div>
                </div>

                <!-- Permission Table -->
                <div x-show="expandedCategories['{{ $category->slug }}']" x-collapse>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Permission
                                    </th>
                                    @foreach($roles as $role)
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[120px]">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-sm">{{ ucfirst($role->name) }}</span>
                                                @if($role->is_system ?? false)
                                                    <span class="px-1.5 py-0.5 text-[10px] bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">System</span>
                                                @endif
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($categoryPermissions as $perm)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-3">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $perm->name }}</span>
                                        </td>
                                        @foreach($roles as $role)
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $hasPerm = $role->permissions->contains('name', $perm->name);
                                                    $isSystemRole = $role->is_system ?? false;
                                                    $isDisabled = ($isSystemRole && !auth()->user()->isOwner());
                                                @endphp
                                                <label class="inline-flex items-center cursor-pointer relative {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                    <input
                                                        type="checkbox"
                                                        class="permission-checkbox sr-only peer"
                                                        data-role-id="{{ $role->id }}"
                                                        data-permission="{{ $perm->name }}"
                                                        {{ $hasPerm ? 'checked' : '' }}
                                                        {{ $isDisabled ? 'disabled' : '' }}
                                                        @change="markChanged()"
                                                    >
                                                    <div class="w-10 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600">
                                                    </div>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($roles) + 1 }}" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No permissions in this category
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                <i class="fas fa-shield-alt text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No categories found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Run the seeder to create default permission categories.</p>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
function permissionMatrix() {
    return {
        saving: false,
        hasChanges: false,
        statusMessage: '',
        statusType: 'success',
        expandedCategories: {},

        init() {
            @foreach($categories as $slug => $name)
                this.expandedCategories['{{ $slug }}'] = true;
            @endforeach
        },

        toggleCategory(slug) {
            this.expandedCategories[slug] = !this.expandedCategories[slug];
        },

        markChanged() {
            this.hasChanges = true;
        },

        saveAll() {
            if (this.saving) return;
            this.saving = true;

            const rolePerms = {};
            @foreach($roles as $role)
                rolePerms[{{ $role->id }}] = [];
            @endforeach

            document.querySelectorAll('.permission-checkbox:checked:not(:disabled)').forEach(cb => {
                const roleId = cb.dataset.roleId;
                const permName = cb.dataset.permission;
                if (rolePerms[roleId]) {
                    rolePerms[roleId].push(permName);
                }
            });

            const promises = Object.entries(rolePerms).map(([roleId, perms]) => {
                return fetch('{{ route("roles.matrix.update") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        role_id: parseInt(roleId),
                        permissions: perms,
                    }),
                });
            });

            Promise.all(promises)
                .then(responses => {
                    const allOk = responses.every(r => r.ok);
                    if (allOk) {
                        this.statusMessage = 'All permissions saved successfully.';
                        this.statusType = 'success';
                        this.hasChanges = false;
                    } else {
                        this.statusMessage = 'Some permissions could not be saved. Please try again.';
                        this.statusType = 'error';
                    }
                })
                .catch(err => {
                    this.statusMessage = 'Network error. Please try again.';
                    this.statusType = 'error';
                    console.error(err);
                })
                .finally(() => {
                    this.saving = false;
                    setTimeout(() => { this.statusMessage = ''; }, 5000);
                });
        }
    };
}
</script>
@endpush
@endsection
