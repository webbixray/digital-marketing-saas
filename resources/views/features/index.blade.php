@extends('layouts.unified')
@section('title', 'Features')

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($features as $feature)
                            <tr>
                                <td><code>{{ $feature->code }}</code></td>
                                <td>{{ $feature->name }}</td>
                                <td>{{ Str::limit($feature->description, 50) }}</td>
                                <td>
                                    <span class="{{ $feature->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        {{ $feature->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('features.flags.show', $feature) }}" class="px-4 py-2 border border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 text-sm font-medium transition-colors">View</a>
                                    <a href="{{ route('features.flags.edit', $feature) }}" class="px-4 py-2 border border-yellow-300 dark:border-yellow-600 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20 text-sm font-medium transition-colors">Edit</a>
                                    <form action="{{ route('features.flags.destroy', $feature) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-4 py-2 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No features found.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $features->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
