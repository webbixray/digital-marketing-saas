@extends('layouts.unified')
@section('title', 'Email Templates')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->subject }}</td>
                                <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($template->category) }}</span></td>
                                <td>
                                    <a href="{{ route('email.templates.show', $template) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('email.templates.edit', $template) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('email.templates.destroy', $template) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table></div>
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

