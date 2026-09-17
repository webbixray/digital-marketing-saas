@extends("layouts.unified")

@section('title', 'Create Role')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Role</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Define a new role with specific permissions.</p>
    </div>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Role Details</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g. Manager, Editor" required>
                            @error('name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Permissions</h3>
                    </div>
                    <div class="p-6">
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
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors w-full">
                            <i class="fas fa-save"></i> Create Role
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
