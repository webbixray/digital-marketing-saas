@extends('layouts.modern')

@section('title', 'Dashboard')

@section('content')
    <!-- Welcome Section -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back, {{ auth()->user()?->name ?? 'User' }}</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Here's what's happening with your social media today.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Posts</p>
                    <p class="stat-value">{{ $stats['total_posts'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-pen-nib text-indigo-600 dark:text-indigo-400 text-xl"></i>
                </div>
            </div>
            <p class="stat-change up"><i class="fas fa-arrow-up"></i> 12% from last month</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Published</p>
                    <p class="stat-value">{{ $stats['published_posts'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
            <p class="stat-change up"><i class="fas fa-arrow-up"></i> 8% from last month</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Scheduled</p>
                    <p class="stat-value">{{ $stats['pending_posts'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
            <p class="stat-change up"><i class="fas fa-arrow-up"></i> 24% from last month</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Failed</p>
                    <p class="stat-value">{{ $stats['failed_posts'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
            <p class="stat-change down"><i class="fas fa-arrow-down"></i> 3% from last month</p>
        </div>
    </div>

    <!-- Platform Stats & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Platform Breakdown -->
        <div class="lg:col-span-2 card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Platform Performance</h3>
                <a href="{{ route('analytics.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View all</a>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    @foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'] as $platform)
                        @php
                            $count = $platformStats[$platform] ?? 0;
                        @endphp
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fab fa-{{ $platform }} text-gray-600 dark:text-gray-300"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $platform }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($count) }} posts</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $count > 0 ? min(($count / max($stats['total_posts'], 1)) * 100, 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <a href="{{ route('activity.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View all</a>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    @forelse($recentActivity ?? [] as $activity)
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-{{ $activity->action === 'created' ? 'plus' : ($activity->action === 'deleted' ? 'trash' : 'edit') }} text-gray-600 dark:text-gray-300 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $activity->description ?? 'Activity' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No recent activity</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
