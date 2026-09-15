@extends('layouts.unified')
@section('title', 'Email Templates')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table class="table table-hover">
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
                                <td><span class="badge badge-info">{{ ucfirst($template->category) }}</span></td>
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

