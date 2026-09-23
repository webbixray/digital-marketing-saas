@extends("layouts.unified")

@section('title', 'Dashboard')

@section('styles')
<style>
    @keyframes subtle-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
    }
    .animate-subtle-pulse {
        animation: subtle-pulse 2.5s ease-in-out infinite;
    }
    @keyframes skeleton-shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .skeleton {
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: skeleton-shimmer 1.5s ease-in-out infinite;
    }
    .dark .skeleton {
        background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
        background-size: 200% 100%;
    }
    .bar-fill {
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .quick-action-card:hover .quick-action-arrow {
        transform: translateX(4px);
    }
</style>
@endsection

@section('content')
    <x-flash-messages />

    <!-- Welcome Section with Gradient -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-6">
        <div>
            <div class="flex items-center gap-3">
                @if(($whiteLabel ?? null)?->logo_url)
                    <img src="{{ $whiteLabel->logo_url }}" alt="{{ $whiteLabel->brand_name }}" class="h-8 w-8 object-contain rounded-lg">
                @else
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bolt text-white text-sm"></i>
                    </div>
                @endif
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back, {{ auth()->user()?->name ?? 'User' }}</h2>
            </div>
            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ ($whiteLabel ?? null)?->brand_name ?? config('app.name') }} — Here's what's happening with your social media today.</p>
        </div>
        <a href="{{ route('social.posts.create') }}" class="animate-subtle-pulse bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2.5 rounded-lg hover:from-indigo-700 hover:to-purple-700 inline-flex items-center gap-2 font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-[1.02]">
            <i class="fas fa-plus"></i> Create Post
        </a>
    </div>

    <!-- Quick Actions Section -->
    <div class="mb-8">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('social.posts.create') }}" class="quick-action-card group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-pen-nib text-white text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Create Post</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">New content</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-indigo-500 quick-action-arrow transition-transform duration-200"></i>
                </div>
            </a>
            <a href="{{ route('ai.index') }}" class="quick-action-card group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-sparkles text-white text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">AI Content</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Generate</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-purple-500 quick-action-arrow transition-transform duration-200"></i>
                </div>
            </a>
            <a href="{{ route('campaigns.create') }}" class="quick-action-card group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bullhorn text-white text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Schedule Campaign</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Plan & publish</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-emerald-500 quick-action-arrow transition-transform duration-200"></i>
                </div>
            </a>
            <a href="{{ route('analytics.index') }}" class="quick-action-card group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">View Analytics</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Performance</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-amber-500 quick-action-arrow transition-transform duration-200"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="mb-8">
        <!-- Skeleton Loading State (shown via x-data conditional in production, here just the markup) -->
        <div x-data="{ loading: false }">
            <!-- Skeleton (visible when loading) -->
            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" style="display: none;">
                @for($i = 0; $i < 4; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-3 flex-1">
                            <div class="skeleton h-4 w-24 rounded"></div>
                            <div class="skeleton h-7 w-16 rounded"></div>
                        </div>
                        <div class="skeleton w-12 h-12 rounded-xl"></div>
                    </div>
                </div>
                @endfor
            </div>

            <!-- Actual Stats -->
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                @php
                    $statCards = [
                        ['label' => 'Total Posts', 'value' => $stats['total_posts'] ?? 0, 'icon' => 'pen-nib', 'gradient' => 'from-indigo-500 to-blue-600', 'lightBg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                        ['label' => 'Published', 'value' => $stats['published_posts'] ?? 0, 'icon' => 'check-circle', 'gradient' => 'from-green-500 to-emerald-600', 'lightBg' => 'bg-green-50 dark:bg-green-900/20'],
                        ['label' => 'Scheduled', 'value' => $stats['pending_posts'] ?? 0, 'icon' => 'clock', 'gradient' => 'from-amber-500 to-orange-600', 'lightBg' => 'bg-amber-50 dark:bg-amber-900/20'],
                        ['label' => 'Failed', 'value' => $stats['failed_posts'] ?? 0, 'icon' => 'exclamation-triangle', 'gradient' => 'from-red-500 to-rose-600', 'lightBg' => 'bg-red-50 dark:bg-red-900/20'],
                    ];
                @endphp
                @foreach($statCards as $stat)
                <div class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 cursor-default">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stat['value']) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br {{ $stat['gradient'] }} rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow duration-200">
                            <i class="fas fa-{{ $stat['icon'] }} text-white text-xl"></i>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Platform Stats & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Platform Breakdown -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Platform Performance</h3>
                <a href="{{ route('analytics.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 inline-flex items-center gap-1 font-medium group">
                    View all <i class="fas fa-arrow-right text-xs transform group-hover:translate-x-1 transition-transform duration-200"></i>
                </a>
            </div>
            <div class="p-6">
                @php
                    $hasAnyPosts = false;
                    foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'] as $p) {
                        if (($platformStats[$p] ?? 0) > 0) {
                            $hasAnyPosts = true;
                            break;
                        }
                    }
                @endphp

                @if(!$hasAnyPosts)
                    <!-- Empty State -->
                    <div class="text-center py-10">
                        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-share-alt text-indigo-400 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">No Platform Data Yet</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Connect your social media accounts to see performance analytics.</p>
                        <a href="{{ route('social.accounts.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                            <i class="fas fa-plus"></i> Connect Account
                        </a>
                    </div>
                @else
                <div class="space-y-5">
                    @foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'] as $platform)
                        @php
                            $count = $platformStats[$platform] ?? 0;
                            $percentage = $stats['total_posts'] > 0 ? min(($count / max($stats['total_posts'], 1)) * 100, 100) : 0;
                            $platformGradients = [
                                'facebook' => 'from-blue-500 to-blue-600',
                                'instagram' => 'from-pink-500 to-purple-600',
                                'twitter' => 'from-sky-400 to-sky-600',
                                'linkedin' => 'from-blue-600 to-blue-800',
                                'tiktok' => 'from-gray-700 to-gray-900',
                                'pinterest' => 'from-red-500 to-red-600',
                                'youtube' => 'from-red-600 to-red-700',
                            ];
                            $gradient = $platformGradients[$platform] ?? 'from-indigo-500 to-indigo-600';
                        @endphp
                        @if($count > 0 || array_key_exists($platform, $platformStats ?? []))
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $platformGradients[$platform] ?? 'from-gray-500 to-gray-600' }} flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i class="fab fa-{{ $platform }} text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $platform }}</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500 font-normal">posts</span></span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                    <div class="bar-fill h-full rounded-full bg-gradient-to-r {{ $gradient }}" style="width: {{ number_format($percentage, 1) }}%"></div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ number_format($percentage, 1) }}%</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <a href="{{ route('activity.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 inline-flex items-center gap-1 font-medium group">
                    View all <i class="fas fa-arrow-right text-xs transform group-hover:translate-x-1 transition-transform duration-200"></i>
                </a>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($recentActivity ?? [] as $activity)
                        <div class="flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg p-2 -mx-2 transition-colors duration-150">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/40 dark:to-purple-900/40 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-{{ $activity->action === 'created' ? 'plus' : ($activity->action === 'deleted' ? 'trash' : 'edit') }} text-indigo-600 dark:text-indigo-400 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $activity->description ?? 'Activity' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-14 h-14 mx-auto mb-3 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-full flex items-center justify-center">
                                <i class="fas fa-inbox text-gray-400 text-lg"></i>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Actions will appear here</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
