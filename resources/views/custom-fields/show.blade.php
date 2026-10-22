@extends('layouts.unified')
@section('title', 'Field: {{ $field->name }}')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Field: {{ $field->name }}</h3></div>
                <div class="p-6 space-y-3">
                    <p><strong class="text-gray-700 dark:text-gray-300">Type:</strong> <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $types[$field->type] ?? $field->type }}</span></p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Model:</strong> <span class="text-gray-700 dark:text-gray-300">{{ $field->model_type }}</span></p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Required:</strong> <span class="px-2 py-1 text-xs font-medium rounded-full {{ $field->is_required ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $field->is_required ? 'Yes' : 'No' }}</span></p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Status:</strong> <span class="px-2 py-1 text-xs font-medium rounded-full {{ $field->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $field->is_active ? 'Active' : 'Inactive' }}</span></p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Sort Order:</strong> <span class="text-gray-700 dark:text-gray-300">{{ $field->sort_order }}</span></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <a href="{{ route('custom-fields.edit', $field) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
                    <form action="{{ route('custom-fields.destroy', $field) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection