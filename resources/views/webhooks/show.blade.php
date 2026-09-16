@extends('layouts.unified')
@section('title', $webhook->name)

@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Webhook Details</h3></div>
            <div class="p-6">
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
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Logs</h3></div>
            <div class="card-body p-0">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead><tr><th>Event</th><th>Status</th><th>Response Time</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $log->event }}</span></td>
                                <td><span class="badge badge-{{ $log->is_success ? 'success' : 'danger' }}">{{ $log->status_code ?? 'Error' }}</span></td>
                                <td>{{ $log->response_time_ms }}ms</td>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No logs</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

