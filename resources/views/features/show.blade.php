@extends('layouts.unified')
@section('title', 'Feature: {{ $feature->name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <p><strong>Code:</strong> <code>{{ $feature->code }}</code></p>
                    <p><strong>Description:</strong> {{ $feature->description ?? '—' }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $feature->is_active ? 'success' : 'secondary' }}">{{ $feature->is_active ? 'Active' : 'Inactive' }}</span></p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('features.flags.edit', $feature) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
                    <form action="{{ route('features.flags.destroy', $feature) }}" method="POST" class="d-inline">
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

