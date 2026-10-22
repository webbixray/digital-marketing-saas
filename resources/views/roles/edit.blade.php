@extends("layouts.unified")

@section('title', 'Edit Role')

@section('content')
    <x-flash-messages />

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Edit Role: {{ ucfirst($role->name) }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Modify role details and permissions.
                </p>
                @if($role->is_system ?? false)
                    <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                        <i class="fas fa-lock"></i> System role — only owners can modify
                    </span>
                @endif
            </div>
            <a href="{{ route('roles.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>

        <form method="POST" action="{{ route('roles.update', $role) }}" x-data="{ activeCategory: 'all' }">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <!-- Role Details -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Role Details</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name</label>
                                <input type="text" name="name" value="{{ old('name', $role->name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required {{ ($role->is_system ?? false) ? 'readonly' : '' }}>
                                @error('name')<div class="form-error text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guard</label>
                                <input type="text" value="{{ $role->guard_name }}" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Permission Matrix -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Permission Matrix</h3>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="document.querySelectorAll('.permission-check:not(:disabled)').forEach(cb => cb.checked = true)" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Select All</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" onclick="document.querySelectorAll('.permission-check:not(:disabled)').forEach(cb => cb.checked = false)" class="text-xs text-gray-500 dark:text-gray-400 hover:underline">Clear All</button>
                            </div>
                        </div>

                        <!-- Category Tabs -->
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <div class="flex overflow-x-auto px-4 gap-1 py-2">
                                <button type="button" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="px-3 py-1.5 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">All</button>
                                @isset($categories)
                                    @foreach($categories as $category)
                                        <button type="button" @click="activeCategory = '{{ $category->slug }}'" :class="activeCategory === '{{ $category->slug }}' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="px-3 py-1.5 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
                                            <i class="fas {{ $category->icon ?? 'fa-tag' }} mr-1"></i>{{ $category->name }}
                                        </button>
                                    @endforeach
                                @endisset
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            @php
                                $allPermissions = \Spatie\Permission\Models\Permission::all();
                                $rolePermNames = $rolePermissions;
                            @endphp

                            @if(isset($categories) && count($categories) > 0)
                                @foreach($categories as $category)
                                    @php
                                        $categoryPerms = $allPermissions->filter(fn($p) => str_contains($p->name, $category->slug))->sortBy('name');
                                    @endphp
                                    <div x-show="activeCategory === 'all' || activeCategory === '{{ $category->slug }}'" class="space-y-2">
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                            <i class="fas {{ $category->icon ?? 'fa-tag' }} text-indigo-500"></i>
                                            {{ $category->name }}
                                            <span class="text-xs text-gray-400 font-normal">({{ $categoryPerms->count() }} permissions)</span>
                                        </h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @forelse($categoryPerms as $permission)
                                                <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                        {{ in_array($permission->name, $rolePermNames, true) ? 'checked' : '' }}
                                                        class="rounded text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 permission-check">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                                </label>
                                            @empty
                                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No permissions in this category</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                {{ in_array($permission->name, $rolePermNames, true) ? 'checked' : '' }}
                                                class="rounded text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 permission-check">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 sticky top-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center justify-center gap-2 font-medium transition-colors">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="{{ route('roles.index') }}" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center justify-center gap-2 font-medium transition-colors">
                                Cancel
                            </a>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Current permissions: <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ count($rolePermissions) }}</span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Role: <span class="font-semibold">{{ ucfirst($role->name) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
