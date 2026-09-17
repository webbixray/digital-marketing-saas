@extends("layouts.unified")

@section('title', 'Roles & Permissions')

@section('content')
    <x-flash-messages />
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Roles & Permissions</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage user roles and their permissions.</p>
        </div>
        <a href="{{ route('roles.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-plus"></i> Create Role
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="table-responsive">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <i class="fas fa-user-shield text-indigo-600 dark:text-indigo-400"></i>
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(3) as $permission)
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $permission->name }}</span>
                                    @endforeach
                                    @if($role->permissions->count() > 3)
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">+{{ $role->permissions->count() - 3 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $role->users_count ?? 0 }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('roles.edit', $role) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-4 py-2 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-user-shield text-4xl mb-4 block"></i>
                                No roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
