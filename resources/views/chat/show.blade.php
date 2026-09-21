@extends('layouts.unified')
@section('title', '#' . $channel->name . ' — Chat')
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
                   class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 {{ $ch->id === $channel->id ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
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
                <p class="p-4 text-gray-500 text-sm">No channels yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white"># {{ $channel->name }}</h4>
                @if($channel->description)
                    <p class="text-sm text-gray-500">{{ $channel->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">{{ $channel->users_count }} members</span>
            </div>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chatMessages">
            @forelse($messages as $msg)
                <div class="flex gap-3 {{ $msg->user_id === auth()->id() ? 'justify-end' : '' }}">
                    @if($msg->user_id !== auth()->id())
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-sm font-semibold flex-shrink-0">
                            {{ substr($msg->user->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="max-w-[70%]">
                        @if($msg->user_id !== auth()->id())
                            <span class="text-xs text-gray-500 ml-1">{{ $msg->user->name }}</span>
                        @endif
                        <div class="px-4 py-2 rounded-lg {{ $msg->user_id === auth()->id() ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' }}">
                            <p class="text-sm whitespace-pre-wrap">{{ $msg->content }}</p>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-400">{{ $msg->created_at->format('g:i A') }}</span>
                            @if($msg->is_edited)
                                <span class="text-xs text-gray-400">(edited)</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            @endforelse
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <form action="{{ route('api.chat.sendMessage', $channel->slug) }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="content" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500" required>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Send</button>
            </form>
        </div>
    </div>
</div>
