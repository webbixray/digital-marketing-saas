@extends('layouts.unified')
@section('title', 'Activity Details')

@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Activity Details</h3></div>
            <div class="p-6">
                <strong>Action:</strong> <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $log->action }}</span><hr>
                <strong>Description:</strong> {{ $log->description }}<hr>
                <strong>User:</strong> {{ $log->user?->name ?? 'System' }}<hr>
                <strong>Date:</strong> {{ $log->created_at->format('M d, Y H:i:s') }}<hr>
                @if($log->metadata)
                    <strong>Metadata:</strong>
                    <pre class="text-sm">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

