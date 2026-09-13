@extends('layouts.app')
@section('title', 'Feature: {{ $feature->name }}')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between">
                <h1>{{ $feature->name }}</h1>
                <a href="{{ route('features.flags.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <p><strong>Code:</strong> <code>{{ $feature->code }}</code></p>
                    <p><strong>Description:</strong> {{ $feature->description ?? '—' }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $feature->is_active ? 'success' : 'secondary' }}">{{ $feature->is_active ? 'Active' : 'Inactive' }}</span></p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('features.flags.edit', $feature) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('features.flags.destroy', $feature) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
