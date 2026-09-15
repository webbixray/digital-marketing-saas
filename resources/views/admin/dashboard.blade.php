@extends("layouts.unified")

@section('title', 'Admin Dashboard')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Overview</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor your platform's health and performance.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Agencies</p>
                    <p class="stat-value">{{ $stats['total_agencies'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-indigo-600 dark:text-indigo-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Active Agencies</p>
                    <p class="stat-value">{{ $stats['active_agencies'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Users</p>
                    <p class="stat-value">{{ $stats['total_users'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Posts</p>
                    <p class="stat-value">{{ $stats['total_social_posts'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-pen-nib text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Status Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card border-l-4 border-green-500">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="stat-value text-green-600 dark:text-green-400">{{ $stats['published_posts'] ?? 0 }}</p>
                    <p class="stat-label">Published</p>
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-yellow-500">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="stat-value text-yellow-600 dark:text-yellow-400">{{ $stats['pending_posts'] ?? 0 }}</p>
                    <p class="stat-label">Scheduled</p>
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-red-500">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="stat-value text-red-600 dark:text-red-400">{{ $stats['failed_posts'] ?? 0 }}</p>
                    <p class="stat-label">Failed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Failed Posts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <a href="{{ route('social.posts.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View all</a>
            </div>
            <div class="card-body">
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
                            <span class="badge {{ $post->status === 'published' ? 'badge-success' : ($post->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">
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
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Failed Posts</h3>
                <a href="{{ route('admin.failed-jobs') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View all</a>
            </div>
            <div class="card-body">
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
                            <form method="POST" action="{{ route('failed-jobs.retry', $post->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-redo"></i> Retry
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
