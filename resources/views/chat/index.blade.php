@extends('layouts.unified')
@section('title', 'Chat')
@section('content')
<x-flash-messages />

<div class="flex h-[calc(100vh-120px)] bg-white dark:bg-gray-800 rounded-lg shadow">
    <!-- Sidebar -->
    <div class="w-64 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Channels</h3>
        </div>
        <div class="flex-1 overflow-y-auto">
            @forelse($channels as $ch)
                <a href="{{ route('chat.show', $ch->slug) }}" 
                   class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 {{ $activeChannel && $activeChannel->id === $ch->id ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-900 dark:text-white"># {{ $ch->name }}</span>
                        @if($ch->users_count > 0)
                            <span class="text-xs text-gray-500">{{ $ch->users_count }}</span>
                        @endif
                    </div>
                    @if($ch->last_message)
                        <p class="text-sm text-gray-500 truncate mt-1">{{ $ch->last_message->content }}</p>
                    @endif
                </a>
            @empty
                <p class="p-4 text-gray-500 text-sm">No channels yet. Create one to get started.</p>
            @endforelse
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
        @if($activeChannel)
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="font-semibold text-gray-900 dark:text-white"># {{ $activeChannel->name }}</h4>
                @if($activeChannel->description)
                    <p class="text-sm text-gray-500">{{ $activeChannel->description }}</p>
                @endif
            </div>
            <div class="flex-1 flex items-center justify-center text-gray-500">
                <p>Select a channel to start chatting</p>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <p class="text-lg font-medium">Welcome to Team Chat</p>
                    <p class="mt-2">Create or select a channel to start collaborating with your team.</p>
                </div>
            </div>
        @endif
    </div>
</div>
