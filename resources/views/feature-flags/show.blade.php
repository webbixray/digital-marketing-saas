@extends('layouts.unified')
@section('title', 'Feature: {{ $flag->feature_name }}')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <p><strong>Key:</strong> <code>{{ $flag->feature_key }}</code></p>
                    <p><strong>Description:</strong> {{ $flag->description ?? '—' }}</p>
                    <p><strong>Enabled:</strong> <span class="badge badge-{{ $flag->enabled ? 'success' : 'secondary' }}">{{ $flag->enabled ? 'On' : 'Off' }}</span></p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('feature-flags.edit', $flag) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('feature-flags.destroy', $flag) }}" method="POST" class="d-inline">
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

