@extends('layouts.modern')

@section('title', 'Create Role')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Role</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Define a new role with specific permissions.</p>
    </div>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Role Details</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <label class="form-label">Role Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Manager, Editor" required>
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Permissions</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($permissions as $permission)
                                <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="rounded">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="fas fa-save"></i> Create Role
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
