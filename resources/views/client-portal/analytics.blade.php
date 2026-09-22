@extends('layouts.unified')

@section('title', 'Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics</h1>
        <p class="text-gray-600 mt-1">Track performance across all your campaigns and platforms</p>
    </div>

    <!-- Overall Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-eye text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Impressions</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($overallMetrics['total_impressions']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-heart text-pink-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Engagements</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($overallMetrics['total_engagements']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-percentage text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Avg Engagement</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($overallMetrics['avg_engagement_rate'], 2) }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-mouse-pointer text-purple-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Clicks</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($overallMetrics['total_clicks']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-paper-plane text-orange-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Posts</p>
                    <p class="text-xl font-bold text-gray-900">{{ $overallMetrics['published_posts'] }}/{{ $overallMetrics['total_posts'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Trend Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Performance Trend</h3>
            <div class="relative h-64">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Platform Breakdown Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Breakdown</h3>
            <div class="relative h-64">
                <canvas id="platformChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Campaign Performance Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Performance Comparison</h3>
        <div class="relative h-72">
            <canvas id="campaignChart"></canvas>
        </div>
    </div>

    <!-- Platform Metrics Table -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Platform Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Platform Details</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Engagement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($platformMetrics as $metric)
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-gray-900 capitalize">{{ $metric->platform }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ number_format($metric->post_count) }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ number_format($metric->total_views) }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ number_format($metric->avg_engagement, 2) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No platform data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Performing Posts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Top Performing Posts</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($topPosts as $post)
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 truncate">{{ Str::limit($post->content, 80) }}</p>
                                <div class="flex items-center mt-2 space-x-4 text-xs text-gray-500">
                                    <span><i class="fas fa-heart mr-1"></i>{{ number_format($post->likes_count) }}</span>
                                    <span><i class="fas fa-comment mr-1"></i>{{ number_format($post->comments_count) }}</span>
                                    <span><i class="fas fa-share mr-1"></i>{{ number_format($post->shares_count) }}</span>
                                </div>
                            </div>
                            <span class="ml-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ number_format($post->engagement_rate, 1) }}%
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">No published posts yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Monthly Trend Chart
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(monthlyTrendCtx, {
        type: 'line',
        data: {
            labels: @json($monthlyTrend->pluck('month')),
            datasets: [
                {
                    label: 'Views',
                    data: @json($monthlyTrend->pluck('total_views')),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Likes',
                    data: @json($monthlyTrend->pluck('total_likes')),
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Clicks',
                    data: @json($monthlyTrend->pluck('total_clicks')),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Platform Breakdown Chart
    const platformCtx = document.getElementById('platformChart').getContext('2d');
    new Chart(platformCtx, {
        type: 'doughnut',
        data: {
            labels: @json($platformMetrics->pluck('platform')->map(fn($p) => ucfirst($p))),
            datasets: [{
                data: @json($platformMetrics->pluck('total_views')),
                backgroundColor: ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Campaign Performance Chart
    const campaignCtx = document.getElementById('campaignChart').getContext('2d');
    new Chart(campaignCtx, {
        type: 'bar',
        data: {
            labels: @json($campaignPerformance->pluck('name')->map(fn($n) => \Illuminate\Support\Str::limit($n, 20))),
            datasets: [
                {
                    label: 'Views',
                    data: @json($campaignPerformance->pluck('views_count')),
                    backgroundColor: '#6366f1'
                },
                {
                    label: 'Likes',
                    data: @json($campaignPerformance->pluck('likes_count')),
                    backgroundColor: '#ec4899'
                },
                {
                    label: 'Comments',
                    data: @json($campaignPerformance->pluck('comments_count')),
                    backgroundColor: '#10b981'
                },
                {
                    label: 'Shares',
                    data: @json($campaignPerformance->pluck('shares_count')),
                    backgroundColor: '#f59e0b'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
@endpush
@endsection
