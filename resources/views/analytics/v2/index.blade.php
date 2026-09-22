@extends('layouts.unified')

@section('title', 'Cross-Platform Analytics 2.0')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .heatmap-cell { transition: all 0.2s; }
    .heatmap-cell:hover { transform: scale(1.1); }
    .trend-line { transition: all 0.3s; }
</style>
@endpush

@section('content')
<x-flash-messages />
<div class="mb-8 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Cross-Platform Analytics 2.0</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Unified performance across Facebook, Instagram, Twitter/X, LinkedIn, TikTok, Pinterest.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('analytics.cross-platform', ['range' => '7']) }}" class="px-3 py-1.5 rounded-lg inline-flex items-center gap-1 text-sm font-medium transition-colors {{ $date_range == 7 ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">7d</a>
        <a href="{{ route('analytics.cross-platform', ['range' => '30']) }}" class="px-3 py-1.5 rounded-lg inline-flex items-center gap-1 text-sm font-medium transition-colors {{ $date_range == 30 ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">30d</a>
        <a href="{{ route('analytics.cross-platform', ['range' => '90']) }}" class="px-3 py-1.5 rounded-lg inline-flex items-center gap-1 text-sm font-medium transition-colors {{ $date_range == 90 ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">90d</a>
    </div>
</div>

<!-- Platform Comparison Table -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Platform Performance Comparison</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Engagement, reach, and impressions across all platforms</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Platform</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Posts</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Published</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Avg Engagement</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Impressions</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Reach</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Clicks</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Likes</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Shares</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'] as $platform)
                    @php $p = $platforms[$platform]; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    {{ $platform == 'facebook' ? 'bg-blue-100 dark:bg-blue-900/30' : '' }}
                                    {{ $platform == 'instagram' ? 'bg-pink-100 dark:bg-pink-900/30' : '' }}
                                    {{ $platform == 'twitter' ? 'bg-sky-100 dark:bg-sky-900/30' : '' }}
                                    {{ $platform == 'linkedin' ? 'bg-blue-100 dark:bg-blue-900/30' : '' }}
                                    {{ $platform == 'tiktok' ? 'bg-gray-100 dark:bg-gray-700' : '' }}
                                    {{ $platform == 'pinterest' ? 'bg-red-100 dark:bg-red-900/30' : '' }}">
                                    <i class="fab fa-{{ $platform == 'twitter' ? 'x-twitter' : $platform }} text-lg
                                        {{ $platform == 'facebook' ? 'text-blue-600 dark:text-blue-400' : '' }}
                                        {{ $platform == 'instagram' ? 'text-pink-600 dark:text-pink-400' : '' }}
                                        {{ $platform == 'twitter' ? 'text-sky-600 dark:text-sky-400' : '' }}
                                        {{ $platform == 'linkedin' ? 'text-blue-700 dark:text-blue-400' : '' }}
                                        {{ $platform == 'tiktok' ? 'text-gray-800 dark:text-gray-200' : '' }}
                                        {{ $platform == 'pinterest' ? 'text-red-600 dark:text-red-400' : '' }}"></i>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ $platform }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">{{ number_format($p['total_posts']) }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">{{ number_format($p['published']) }}</td>
                        <td class="px-6 py-4 text-center text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $p['avg_engagement_rate'] >= 4 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $p['avg_engagement_rate'] >= 2 && $p['avg_engagement_rate'] < 4 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $p['avg_engagement_rate'] >= 1 && $p['avg_engagement_rate'] < 2 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $p['avg_engagement_rate'] < 1 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                {{ number_format($p['avg_engagement_rate'], 2) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300 font-semibold">{{ number_format($p['total_impressions']) }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">{{ number_format($p['total_reach']) }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">{{ number_format($p['total_clicks']) }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">{{ number_format($p['total_likes']) }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">{{ number_format($p['total_shares']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <td class="px-6 py-3 text-sm font-semibold text-gray-900 dark:text-white">Total</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->sum('total_posts')) }}</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->sum('published')) }}</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->avg('avg_engagement_rate'), 2) }}%</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($total_impressions) }}</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->sum('total_reach')) }}</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->sum('total_clicks')) }}</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->sum('total_likes')) }}</td>
                    <td class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ number_format(collect($platforms)->sum('total_shares')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Pie Chart: Traffic by Platform -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Traffic by Platform</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Impression distribution</p>
        </div>
        <div class="p-6">
            <canvas id="trafficPieChart" width="400" height="300"></canvas>
        </div>
    </div>

    <!-- Trend Line: Engagement Over Time -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Engagement Trend</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Average engagement rate over time</p>
        </div>
        <div class="p-6">
            <canvas id="engagementTrendChart" width="400" height="300"></canvas>
        </div>
    </div>
</div>

<!-- Heatmap: Best Performing Content -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white text-lg">Best Performing Content Heatmap</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Posts by platform and performance tier (engagement rate)</p>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Platform</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-green-600 dark:text-green-400 uppercase">Excellent (≥4%)</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase">Good (2-4%)</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-yellow-600 dark:text-yellow-400 uppercase">Average (1-2%)</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-red-600 dark:text-red-400 uppercase">Low (&lt;1%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'] as $platform)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $platform }}</td>
                            @foreach(['excellent', 'good', 'average', 'low'] as $tier)
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $count = $heatmap[$platform][$tier];
                                        $tierTotal = array_sum($heatmap[$platform]);
                                        $opacity = $tierTotal > 0 ? max(0.2, $count / max($tierTotal, 1)) : 0.1;
                                    @endphp
                                    <div class="heatmap-cell inline-flex items-center justify-center w-16 h-10 rounded-lg text-sm font-semibold
                                        {{ $tier == 'excellent' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $tier == 'good' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $tier == 'average' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $tier == 'low' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}"
                                        style="opacity: {{ $opacity }};">
                                        {{ $count }}
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Audience Demographics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Age Groups -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Age Distribution</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Audience by age group</p>
        </div>
        <div class="p-6">
            <canvas id="ageChart" width="300" height="250"></canvas>
        </div>
    </div>

    <!-- Gender -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Gender Split</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Audience gender distribution</p>
        </div>
        <div class="p-6">
            <canvas id="genderChart" width="300" height="250"></canvas>
        </div>
    </div>

    <!-- Top Locations -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Top Locations</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Audience by country</p>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($demographics['top_locations'] as $location)
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $location['name'] }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $location['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-2.5 rounded-full transition-all" style="width: {{ $location['percentage'] * 2.5 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Traffic Pie Chart
    const trafficCtx = document.getElementById('trafficPieChart').getContext('2d');
    new Chart(trafficCtx, {
        type: 'pie',
        data: {
            labels: ['Facebook', 'Instagram', 'Twitter/X', 'LinkedIn', 'TikTok', 'Pinterest'],
            datasets: [{
                data: [
                    @foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'] as $p)
                        {{ $traffic_by_platform[$p]['percentage'] }},
                    @endforeach
                ],
                backgroundColor: ['#1877F2', '#E4405F', '#1DA1F2', '#0A66C2', '#000000', '#E60023'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ': ' + ctx.raw + '%';
                        }
                    }
                }
            }
        }
    });

    // Engagement Trend Line Chart
    const trendCtx = document.getElementById('engagementTrendChart').getContext('2d');
    const trendLabels = [];
    const trendDatasets = [];
    const platformColors = {
        facebook: '#1877F2',
        instagram: '#E4405F',
        twitter: '#1DA1F2',
        linkedin: '#0A66C2',
        tiktok: '#000000',
        pinterest: '#E60023'
    };
    const platformData = {};

    @foreach($engagement_trend as $date => $platforms)
        trendLabels.push('{{ $date }}');
        @foreach($platforms as $platform => $value)
            if (!platformData['{{ $platform }}']) platformData['{{ $platform }}'] = [];
            platformData['{{ $platform }}'].push({{ $value }});
        @endforeach
    @endforeach

    const platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'];
    platforms.forEach(function(platform) {
        trendDatasets.push({
            label: platform.charAt(0).toUpperCase() + platform.slice(1),
            data: platformData[platform] || [],
            borderColor: platformColors[platform],
            backgroundColor: platformColors[platform] + '20',
            borderWidth: 2,
            tension: 0.4,
            fill: false
        });
    });

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: trendDatasets
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15 } }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Avg Engagement Rate (%)' } },
                x: { title: { display: true, text: 'Date' } }
            }
        }
    });

    // Age Distribution Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: ['18-24', '25-34', '35-44', '45-54', '55+'],
            datasets: [{
                data: [
                    @foreach($demographics['age_groups'] as $val)
                        {{ $val }},
                    @endforeach
                ],
                backgroundColor: ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'],
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: '%' } },
                x: { title: { display: true, text: 'Age Group' } }
            }
        }
    });

    // Gender Distribution Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{
                data: [
                    {{ $demographics['gender']['male'] }},
                    {{ $demographics['gender']['female'] }},
                    {{ $demographics['gender']['other'] }}
                ],
                backgroundColor: ['#6366f1', '#ec4899', '#a78bfa'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15 } }
            }
        }
    });
});
</script>
@endpush
@endsection
