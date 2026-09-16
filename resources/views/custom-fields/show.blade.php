@extends('layouts.unified')
@section('title', 'Field: {{ $field->name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <p><strong>Type:</strong> <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $types[$field->type] ?? $field->type }}</span></p>
                    <p><strong>Model:</strong> {{ $field->model_type }}</p>
                    <p><strong>Required:</strong> <span class="badge badge-{{ $field->is_required ? 'danger' : 'secondary' }}">{{ $field->is_required ? 'Yes' : 'No' }}</span></p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $field->is_active ? 'success' : 'secondary' }}">{{ $field->is_active ? 'Active' : 'Inactive' }}</span></p>
                    <p><strong>Sort Order:</strong> {{ $field->sort_order }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('custom-fields.edit', $field) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
                    <form action="{{ route('custom-fields.destroy', $field) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

