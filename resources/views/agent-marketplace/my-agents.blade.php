@extends('layouts.unified')
@section('title', 'My Agents')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="hover:text-gray-700"><a href="{{ route('agent-marketplace.index') }}">Marketplace</a></li>
 <li class="text-gray-900 font-medium">My Agents</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">My Agents</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your installed AI agents.</p>
        </div>
        <a href="{{ route('agent-marketplace.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors self-start">
            <i class="fas fa-store mr-1"></i> Browse Marketplace
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($installed as $agent)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i class="{{ $agent->icon ?? 'fas fa-robot' }} text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $agent->name }}</h4>
                        <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2 py-0.5 rounded-full"><i class="fas fa-check mr-1"></i>Active</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ Str::limit($agent->description, 100) }}</p>
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <span><i class="fas fa-folder mr-1"></i>{{ $agent->category->name ?? 'General' }}</span>
                    <span><i class="fas fa-star text-yellow-500 mr-1"></i>{{ number_format($agent->rating_avg, 1) }}</span>
                </div>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-700 px-6 py-3 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
                <a href="{{ route('agent-marketplace.configure', ['item' => $agent->id]) }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium">
                    <i class="fas fa-cog mr-1"></i> Configure
                </a>
                <form method="POST" action="{{ route('agent-marketplace.uninstall') }}" class="inline" onsubmit="return confirm('Are you sure you want to uninstall this agent?')">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $agent->id }}">
                    <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 text-sm font-medium">
                        <i class="fas fa-trash-alt mr-1"></i> Uninstall
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
            <i class="fas fa-robot text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400 mb-4 text-lg">No agents installed yet.</p>
            <a href="{{ route('agent-marketplace.index') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                Browse Marketplace
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
