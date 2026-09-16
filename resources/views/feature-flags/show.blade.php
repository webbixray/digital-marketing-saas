@extends('layouts.unified')
@section('title', 'Feature: {{ $flag->feature_name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <p><strong>Key:</strong> <code>{{ $flag->feature_key }}</code></p>
                    <p><strong>Description:</strong> {{ $flag->description ?? '—' }}</p>
                    <p><strong>Enabled:</strong> <span class="badge badge-{{ $flag->enabled ? 'success' : 'secondary' }}">{{ $flag->enabled ? 'On' : 'Off' }}</span></p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('feature-flags.edit', $flag) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
                    <form action="{{ route('feature-flags.destroy', $flag) }}" method="POST" class="d-inline">
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

