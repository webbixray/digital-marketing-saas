@extends('layouts.unified')

@section('title', 'Instagram Metrics')

@section('breadcrumb')
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li><a href="{{ route('instagram.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Instagram</a></li>
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li class="text-gray-900 dark:text-white font-medium">Metrics</li>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Instagram Metrics: {{ $account->platform_display_name }}</h1>

        @if($profile['success'])
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Profile</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Username</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['username'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Followers</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['followers_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Following</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['follows_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Media</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['media_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($insights['success'])
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Insights</h2>
                <div class="space-y-2 text-gray-700 dark:text-gray-300">
                    @foreach($insights['data'] as $metric)
                        <p>{{ $metric['title'] }}: {{ $metric['values'][0]['value'] ?? 0 }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
