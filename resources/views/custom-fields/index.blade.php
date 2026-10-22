@extends('layouts.unified')
@section('title', 'Custom Fields')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Model</th>
                        <th>Required</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fields as $field)
                    <tr>
                        <td>{{ $field->name }}</td>
                        <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $types[$field->type] ?? $field->type }}</span></td>
                        <td>{{ $field->model_type }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $field->is_required ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $field->is_required ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $field->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $field->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="flex items-center gap-2">
                            <a href="{{ route('custom-fields.show', $field) }}" class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">View</a>
                            <a href="{{ route('custom-fields.edit', $field) }}" class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">Edit</a>
                            <form action="{{ route('custom-fields.destroy', $field) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-6 text-gray-500 dark:text-gray-400">No custom fields found.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $fields->links() }}
        </div>
    </div>
</div>
@endsection