@extends("layouts.unified")

@section('title', 'Admin Dashboard')

@section('content')
<x-flash-messages />
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Overview</h2>
    <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor your platform's health and performance.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Agencies</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_agencies'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-indigo-600 dark:text-indigo-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Agencies</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_agencies'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_users'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Posts</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_social_posts'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-pen-nib text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Post Status Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 border-l-4 border-green-500 p-6">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-check text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['published_posts'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Published</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 border-l-4 border-yellow-500 p-6">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_posts'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Scheduled</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 border-l-4 border-red-500 p-6">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed_posts'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Failed Posts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
            <a href="{{ route('social.posts.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View all</a>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($recentActivity ?? [] as $post)
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <i class="fab fa-{{ $post->platform }} text-gray-600 dark:text-gray-300 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ ucfirst($post->platform) }} — {{ $post->agency->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $post->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($post->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300') }}">
                        {{ ucfirst($post->status) }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent activity</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Failed Posts -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">Failed Posts</h3>
            <a href="{{ route('admin.failed-jobs') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View all</a>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($failedPosts ?? [] as $post)
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ ucfirst($post->platform) }}</p>
                        <p class="text-xs text-red-600 dark:text-red-400 truncate">{{ $post->error_message ?? 'Unknown error' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $post->failed_at ? \Carbon\Carbon::parse($post->failed_at)->diffForHumans() : '' }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.retry-job', $post->id) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
                            <i class="fas fa-redo mr-1"></i>Retry
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No failed posts</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
