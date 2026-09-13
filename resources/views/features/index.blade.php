@extends('layouts.app')
@section('title', 'Features')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Features</h1>
                <a href="{{ route('features.flags.create') }}" class="btn btn-primary">Create Feature</a>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table class="table table-hover">
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
                                    <span class="badge badge-{{ $feature->is_active ? 'success' : 'secondary' }}">
                                        {{ $feature->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('features.flags.show', $feature) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('features.flags.edit', $feature) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('features.flags.destroy', $feature) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No features found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $features->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
