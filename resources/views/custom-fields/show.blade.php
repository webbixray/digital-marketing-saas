@extends('layouts.unified')
@section('title', 'Field: {{ $field->name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <p><strong>Type:</strong> <span class="badge badge-info">{{ $types[$field->type] ?? $field->type }}</span></p>
                    <p><strong>Model:</strong> {{ $field->model_type }}</p>
                    <p><strong>Required:</strong> <span class="badge badge-{{ $field->is_required ? 'danger' : 'secondary' }}">{{ $field->is_required ? 'Yes' : 'No' }}</span></p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $field->is_active ? 'success' : 'secondary' }}">{{ $field->is_active ? 'Active' : 'Inactive' }}</span></p>
                    <p><strong>Sort Order:</strong> {{ $field->sort_order }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('custom-fields.edit', $field) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('custom-fields.destroy', $field) }}" method="POST" class="d-inline">
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

