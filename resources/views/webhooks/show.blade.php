@extends('layouts.unified')
@section('title', $webhook->name)

@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Webhook Details</h3></div>
            <div class="card-body">
                <strong>Name:</strong> {{ $webhook->name }}<hr>
                <strong>URL:</strong> <small>{{ $webhook->url }}</small><hr>
                <strong>Events:</strong> @foreach($webhook->events ?? [] as $event)<span class="badge badge-info mr-1">{{ $event }}</span>@endforeach<hr>
                <strong>Status:</strong> <span class="badge badge-{{ $webhook->is_active ? 'success' : 'secondary' }}">{{ $webhook->is_active ? 'Active' : 'Inactive' }}</span><hr>
                <strong>Total Calls:</strong> {{ $webhook->total_calls }}<hr>
                <strong>Failed:</strong> {{ $webhook->failed_calls }}<hr>
                <strong>Last Triggered:</strong> {{ $webhook->last_triggered_at?->diffForHumans() ?? 'Never' }}<hr>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Logs</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Event</th><th>Status</th><th>Response Time</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td><span class="badge badge-info">{{ $log->event }}</span></td>
                                <td><span class="badge badge-{{ $log->is_success ? 'success' : 'danger' }}">{{ $log->status_code ?? 'Error' }}</span></td>
                                <td>{{ $log->response_time_ms }}ms</td>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No logs</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

