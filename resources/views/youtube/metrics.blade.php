@extends('layouts.unified')

@section('title', 'YouTube Channel')

@section('breadcrumb')
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li><a href="{{ route('youtube.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">YouTube</a></li>
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li class="text-gray-900 dark:text-white font-medium">Metrics</li>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">YouTube Channel: {{ $account->platform_display_name }}</h1>

        @if($channelStats['success'])
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Channel Statistics</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Subscribers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $channelStats['data']['subscriber_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Videos</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $channelStats['data']['video_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Views</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $channelStats['data']['view_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($videos['success'])
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Recent Videos</h2>
                <ul class="space-y-2">
                    @foreach($videos['videos'] as $video)
                        <li class="flex items-center justify-between text-gray-700 dark:text-gray-300">
                            <span>{{ $video['title'] }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $video['view_count'] }} views</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
