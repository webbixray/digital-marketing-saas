@extends('layouts.unified')
@section('title', 'Create Feature')
@section('content')
<x-flash-messages />
<div class="max-w-2xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('features.flags.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Features</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-white">Create</span>
    </nav>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Create Feature</h3>
        </div>
        <form action="{{ route('features.flags.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required placeholder="Feature name">
                </div>
                <div>
                    <label for="code" class="form-label">Code</label>
                    <input type="text" name="code" id="code" class="form-input" value="{{ old('code') }}" required placeholder="e.g., advanced-analytics">
                </div>
                <div>
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-input" rows="3" placeholder="Describe the feature...">{{ old('description') }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" value="1" {{ old('is_active') ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="submit" class="btn-primary">Create Feature</button>
                <a href="{{ route('features.flags.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
