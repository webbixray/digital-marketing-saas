@extends('layouts.unified')

@section('title', 'Pinterest Integration')

@section('breadcrumb')
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li><a href="{{ route('pinterest.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Integration</a></li>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Pinterest Integration</h1>

        @if(session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">{{ session('error') }}</div>
        @endif

        @if($pinterestAccounts->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 mb-4">No Pinterest accounts connected.</p>
            <a href="{{ route('pinterest.connect') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Connect Pinterest Account</a>
        @else
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Connected Accounts</h2>
            <ul class="space-y-2">
                @foreach($pinterestAccounts as $account)
                    <li class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $account->platform_display_name ?? $account->platform_username }}
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full {{ $account->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' }}">
                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
