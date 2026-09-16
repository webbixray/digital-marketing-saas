@extends('layouts.unified')
@section('title', 'Template: {{ $template->name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <p><strong>Platform:</strong> <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($template->platform) }}</span></p>
                    <p><strong>Type:</strong> {{ ucfirst($template->type) }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $template->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($template->status) }}</span></p>
                    <hr>
                    <h5>Content</h5>
                    <div class="border p-3 bg-light">
                        <pre class="mb-0">{{ $template->template_content }}</pre>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('content-templates.edit', $template) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
                    <form action="{{ route('content-templates.destroy', $template) }}" method="POST" class="d-inline">
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

