@extends("layouts.unified")

@section('title', 'Roles & Permissions')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Roles & Permissions</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Manage user roles and their permissions.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('roles.matrix') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="fas fa-th"></i> Permission Matrix
                </a>
                @can('create roles')
                    <a href="{{ route('roles.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                        <i class="fas fa-plus"></i> Create Role
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Role Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($roles as $role)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Card Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                <i class="fas fa-user-shield text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($role->name) }}</h3>
                                @if($role->is_system ?? false)
                                    <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">System</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="px-6 py-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Permissions</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $role->permissions->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Members</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $role->users_count ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Type</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $role->agency_id ? 'Agency' : 'Global' }}
                        </span>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-end gap-2">
                    @can('update roles')
                        <a
                            href="{{ route('roles.edit', $role) }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                        >
                            <i class="fas fa-pen text-xs"></i> Edit
                        </a>
                    @endcan
                    @can('delete roles')
                        @if(! ($role->is_system ?? false))
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                >
                                    <i class="fas fa-trash text-xs"></i> Delete
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                <i class="fas fa-user-shield text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No roles found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create a new role to get started.</p>
            </div>
        @endforelse>
    </div>
@endsection
