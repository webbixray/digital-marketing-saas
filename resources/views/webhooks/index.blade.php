@extends('layouts.unified')
@section('title', 'Webhooks')

@section('content')
<div class="space-y-6">
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-plug mr-2"></i>Webhooks</h3>
        <div class="card-tools">
            <a href="{{ route('webhooks.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"><i class="fas fa-plus mr-1"></i> New Webhook</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
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

