@extends('layouts.unified')
@section('title', 'Webhooks')

@section('content')
<div class="space-y-6">
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Webhooks</h3>
        <div class="card-tools">
            <a href="{{ route('webhooks.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Webhook</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead><tr><th>Name</th><th>URL</th><th>Events</th><th>Calls</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($webhooks as $webhook)
                    <tr>
                        <td><a href="{{ route('webhooks.show', $webhook) }}">{{ $webhook->name }}</a></td>
                        <td><small class="text-muted">{{ Str::limit($webhook->url, 40) }}</small></td>
                        <td>{{ count($webhook->events ?? []) }} events</td>
                        <td>{{ $webhook->total_calls }} ({{ $webhook->failed_calls }} failed)</td>
                        <td><span class="badge badge-{{ $webhook->is_active ? 'success' : 'secondary' }}">{{ $webhook->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('webhooks.edit', $webhook) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('webhooks.destroy', $webhook) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No webhooks configured</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

