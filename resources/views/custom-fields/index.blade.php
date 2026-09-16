@extends('layouts.unified')
@section('title', 'Custom Fields')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
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
                                    <span class="badge badge-{{ $field->is_required ? 'danger' : 'secondary' }}">
                                        {{ $field->is_required ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $field->is_active ? 'success' : 'secondary' }}">
                                        {{ $field->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('custom-fields.show', $field) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('custom-fields.edit', $field) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('custom-fields.destroy', $field) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">No custom fields found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $fields->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

