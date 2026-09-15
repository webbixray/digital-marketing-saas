@extends('layouts.unified')
@section('title', 'Template: {{ $template->name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <p><strong>Platform:</strong> <span class="badge badge-info">{{ ucfirst($template->platform) }}</span></p>
                    <p><strong>Type:</strong> {{ ucfirst($template->type) }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $template->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($template->status) }}</span></p>
                    <hr>
                    <h5>Content</h5>
                    <div class="border p-3 bg-light">
                        <pre class="mb-0">{{ $template->template_content }}</pre>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('content-templates.edit', $template) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('content-templates.destroy', $template) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

