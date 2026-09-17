@extends('layouts.unified')

@section('title', 'TikTok Profile')

@section('breadcrumb')
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li><a href="{{ route('tiktok.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">TikTok</a></li>
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li class="text-gray-900 dark:text-white font-medium">Metrics</li>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">TikTok Profile: {{ $account->platform_display_name }}</h1>

        @if($profile['success'])
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Profile Details</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-center mb-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Username</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['username'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Display Name</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['display_name'] ?? 'N/A' }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bio</p>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $profile['data']['bio_description'] ?? 'N/A' }}</p>
                    </div>
                </div>
                <hr class="border-gray-200 dark:border-gray-600 my-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Followers</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['follower_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Following</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['following_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Likes</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['likes_count'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Videos</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $profile['data']['video_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
