@extends('layouts.unified')
@section('title', 'Inbox')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Unified Inbox</h1>
            <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Inbox</li>
            </ol>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                Unread: {{ $inbox['unread_count'] ?? 0 }}
            </span>
            <span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                Total: {{ $inbox['total_count'] ?? 0 }}
            </span>
        </div>
    </div>

    <!-- Messages Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Messages</h3>
        </div>
        <div class="p-6">
            @if(!empty($inbox['messages']))
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Platform</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Content</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Received</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($inbox['messages'] as $message)
                                @if(!is_object($message)) @continue; @endif
                                <tr class="{{ $message->status === 'unread' ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucfirst($message->platform) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        {{ Str::limit($message->content, 100) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($message->status === 'unread')
                                            <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 text-xs font-medium px-2.5 py-0.5 rounded-full">Unread</span>
                                        @else
                                            <span class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium px-2.5 py-0.5 rounded-full">Read</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $message->received_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('unified-inbox.show', $message) }}" class="px-3 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox fa-3x text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">No messages yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
