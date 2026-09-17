@extends('layouts.unified')
@section('title', 'Message')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ ucfirst($message->platform) }} Message</h1>
            <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('inbox.index') }}" class="hover:text-indigo-600">Inbox</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Message</li>
            </ol>
        </div>
        <a href="{{ route('inbox.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Inbox
        </a>
    </div>

    <!-- Message Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i class="fas fa-user text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $message->author_name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Via {{ ucfirst($message->platform) }}</p>
                    </div>
                </div>
                <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ ucfirst($message->status ?? 'unread') }}
                </span>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-clock"></i>
                    <span>Received: {{ $message->received_at->toDayDateTimeString() }}</span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                    <p class="text-gray-900 dark:text-white leading-relaxed whitespace-pre-wrap">{{ $message->content }}</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors inline-flex items-center gap-2">
                <i class="fas fa-reply"></i> Reply
            </button>
            <button class="px-4 py-2 border border-red-300 text-red-600 dark:border-red-700 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors inline-flex items-center gap-2">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>
@endsection
