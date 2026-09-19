@extends('layouts.unified')
@section('title', $webhook->name)

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Webhook Details</h3></div>
                <div class="p-6 space-y-3">
                    <p><strong>Name:</strong> {{ $webhook->name }}</p>
                    <p><strong>URL:</strong> <small>{{ $webhook->url }}</small></p>
                    <p><strong>Events:</strong> @foreach($webhook->events ?? [] as $event)<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full mr-1 dark:bg-blue-900 dark:text-blue-300">{{ $event }}</span>@endforeach</p>
                    <p><strong>Status:</strong> <span class="{{ $webhook->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $webhook->is_active ? 'Active' : 'Inactive' }}</span></p>
                    <p><strong>Total Calls:</strong> {{ $webhook->total_calls }}</p>
                    <p><strong>Failed:</strong> {{ $webhook->failed_calls }}</p>
                    <p><strong>Last Triggered:</strong> {{ $webhook->last_triggered_at?->diffForHumans() ?? 'Never' }}</p>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Logs</h3></div>
                <div class="p-0">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead><tr><th>Event</th><th>Status</th><th>Response Time</th><th>Date</th></tr></thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $log->event }}</span></td>
                                        <td><span class="{{ $log->is_success ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $log->status_code ?? 'Error' }}</span></td>
                                        <td>{{ $log->response_time_ms }}ms</td>
                                        <td>{{ $log->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-gray-500 dark:text-gray-400">No logs</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
