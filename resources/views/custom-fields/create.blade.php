@extends('layouts.unified')
@section('title', 'Create Custom Field')
@section('content')
<x-flash-messages />
<div class="max-w-2xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('custom-fields.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Custom Fields</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-white">Create</span>
    </nav>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Create Custom Field</h3>
        </div>
        <form action="{{ route('custom-fields.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required placeholder="Field name">
                </div>
                <div>
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-input" required>
                        <option value="">Select type...</option>
                        <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Text</option>
                        <option value="number" {{ old('type') === 'number' ? 'selected' : '' }}>Number</option>
                        <option value="date" {{ old('type') === 'date' ? 'selected' : '' }}>Date</option>
                        <option value="select" {{ old('type') === 'select' ? 'selected' : '' }}>Select</option>
                        <option value="checkbox" {{ old('type') === 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                    </select>
                </div>
                <div>
                    <label for="options" class="form-label">Options <span class="text-gray-400 dark:text-gray-500 font-normal">(one per line, for select type only)</span></label>
                    <textarea name="options" id="options" class="form-input" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3">{{ old('options') }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_required" id="is_required" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" value="1" {{ old('is_required') ? 'checked' : '' }}>
                    <label for="is_required" class="text-sm font-medium text-gray-700 dark:text-gray-300">Required</label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="submit" class="btn-primary">Create Field</button>
                <a href="{{ route('custom-fields.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
