@extends('layouts.modern')

@section('title', 'Roles & Permissions')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Roles & Permissions</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage user roles and their permissions.</p>
        </div>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Role
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
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
                                        <span class="badge badge-info">{{ $permission->name }}</span>
                                    @endforeach
                                    @if($role->permissions->count() > 3)
                                        <span class="badge badge-secondary">+{{ $role->permissions->count() - 3 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $role->users_count ?? 0 }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
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
            </table>
        </div>
    </div>
@endsection
