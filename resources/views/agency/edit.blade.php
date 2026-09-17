@extends("layouts.unified")

@section('title', 'Edit Agency')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Agency</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Update your agency information.</p>
    </div>

    <form method="POST" action="{{ route('agency.update') }}">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agency Name</label>
                    <input type="text" name="name" value="{{ old('name', $agency->name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                    <input type="url" name="website" value="{{ old('website', $agency->website) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $agency->description) }}</textarea>
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Save Changes</button>
            </div>
        </div>
    </form>
@endsection