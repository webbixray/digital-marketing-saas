@extends('layouts.unified')
@section('title', 'Activity Details')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Activity Details</h3></div>
                <div class="p-6 space-y-3">
                    <div><strong>Action:</strong> <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $log->action }}</span></div>
                    <div><strong>Description:</strong> {{ $log->description }}</div>
                    <div><strong>User:</strong> {{ $log->user?->name ?? 'System' }}</div>
                    <div><strong>Date:</strong> {{ $log->created_at->format('M d, Y H:i:s') }}</div>
                    @if($log->metadata)
                        <div>
                            <strong>Metadata:</strong>
                            <pre class="text-sm mt-1 bg-gray-50 dark:bg-gray-700 rounded p-3 overflow-x-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
