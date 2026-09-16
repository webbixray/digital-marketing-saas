@extends('layouts.unified')
@section('title', 'Content Templates')

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
                                <th>Platform</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($template->platform) }}</span></td>
                                <td>{{ ucfirst($template->type) }}</td>
                                <td>
                                    <span class="badge badge-{{ $template->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($template->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('content-templates.show', $template) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('content-templates.edit', $template) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('content-templates.destroy', $template) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No templates found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

