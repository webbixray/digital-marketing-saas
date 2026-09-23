@extends("layouts.unified")

@section('title', 'Create Role')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Role</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Define a new role with specific permissions.</p>
            </div>
            <a href="{{ route('roles.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('roles.store') }}" x-data="{ activeCategory: 'all' }">
        @csrf
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
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g. Manager, Editor" required>
                            @error('name')<div class="form-error text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Brief description of this role">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Permissions by Category -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Permissions</h3>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAll()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Select All</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="deselectAll()" class="text-xs text-gray-500 dark:text-gray-400 hover:underline">Clear All</button>
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
                        @if(isset($categories) && $categories->count() > 0)
                            @foreach($categories as $category)
                                <div x-show="activeCategory === 'all' || activeCategory === '{{ $category->slug }}'" class="space-y-2">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <i class="fas {{ $category->icon ?? 'fa-tag' }} text-indigo-500"></i>
                                        {{ $category->name }}
                                        <span class="text-xs text-gray-400 font-normal">({{ $category->permissions->count() ?? 0 }} permissions)</span>
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @forelse($category->permissions ?? [] as $permission)
                                            <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 permission-check">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                            </label>
                                        @empty
                                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No permissions assigned to this category</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 permission-check">
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
                            <i class="fas fa-save"></i> Create Role
                        </button>
                        <a href="{{ route('roles.index') }}" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center justify-center gap-2 font-medium transition-colors">
                            Cancel
                        </a>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Selected: <span id="selected-count" class="font-semibold text-indigo-600 dark:text-indigo-400">0</span> permissions
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.querySelectorAll('.permission-check').forEach(cb => {
            cb.addEventListener('change', () => {
                document.getElementById('selected-count').textContent =
                    document.querySelectorAll('.permission-check:checked').length;
            });
        });
    </script>
    @endpush
@endsection
