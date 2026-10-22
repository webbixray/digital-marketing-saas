@extends('layouts.unified')
@section('title', 'Feature: {{ $flag->feature_name }}')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <p><strong>Key:</strong> <code>{{ $flag->feature_key }}</code></p>
            <p><strong>Description:</strong> {{ $flag->description ?? '—' }}</p>
            <p><strong>Enabled:</strong> <span class="{{ $flag->enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $flag->enabled ? 'On' : 'Off' }}</span></p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('feature-flags.edit', $flag) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
            <form action="{{ route('feature-flags.destroy', $flag) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="return confirm('Delete?')">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection
