@extends("layouts.unified")

@section('title', 'Optimal Posting Times')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-clock text-blue-500 mr-2"></i>Optimal Posting Times
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Find the best times to post for maximum engagement.</p>
        </div>
        <a href="{{ route('predictive.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- Platform Selector -->
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($platforms as $p)
            <a href="{{ route('predictive.optimal-times', ['platform' => $p]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
               {{ $selected_platform === $p ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ ucfirst($p) }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Best Times -->
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-star text-yellow-500 mr-2"></i>Best Posting Times
            </h3>
            @if(!empty($best_times['best_times']))
                <div class="space-y-3">
                    @foreach($best_times['best_times'] as $time)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <i class="fas fa-clock text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $time['formatted'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $time['post_count'] }} posts</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-blue-600">{{ number_format($time['avg_engagement'], 2) }}%</p>
                                <p class="text-xs text-gray-500">avg engagement</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No data for this platform.</p>
            @endif
        </div>

        <!-- Peak Time & Confidence -->
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-trophy text-green-500 mr-2"></i>Peak Performance
            </h3>
            @if($heatmap['peak_day'] ?? false)
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Best time to post on {{ ucfirst($selected_platform) }}</p>
                    <p class="text-4xl font-bold text-gray-900 dark:text-white mb-2">{{ $heatmap['formatted_peak'] }}</p>
                    <p class="text-lg text-blue-600 font-semibold">{{ number_format($heatmap['peak_engagement'], 2) }}% avg engagement</p>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">Insufficient data to determine peak time.</p>
            @endif
        </div>
    </div>

    <!-- Engagement Heatmap -->
    <div class="stat-card mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-th text-orange-500 mr-2"></i>Engagement Heatmap (Day × Hour)
        </h3>
        @if(!empty($heatmap['heatmap']))
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-2 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Day</th>
                            @for($h = 0; $h < 24; $h += 2)
                                <th class="px-2 py-2 text-center font-medium text-gray-500">{{ sprintf('%02d', $h) }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($heatmap['heatmap'] as $day => $hours)
                            <tr>
                                <td class="px-2 py-1 font-medium text-gray-700 dark:text-gray-300">{{ $day }}</td>
                                @for($h = 0; $h < 24; $h += 2)
                                    @php
                                        $val = ($hours[$h] ?? 0) + ($hours[$h + 1] ?? 0);
                                        $intensity = min(1, $val / max($heatmap['peak_engagement'], 1));
                                        $bg = $intensity > 0.7 ? 'bg-green-200 dark:bg-green-800' : ($intensity > 0.4 ? 'bg-yellow-100 dark:bg-yellow-900' : ($intensity > 0 ? 'bg-gray-100 dark:bg-gray-700' : ''));
                                    @endphp
                                    <td class="px-2 py-1 text-center {{ $bg }}">
                                        {{ $val > 0 ? number_format($val, 1) : '' }}
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Audience Activity -->
    <div class="stat-card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-users text-purple-500 mr-2"></i>Audience Activity by Hour
        </h3>
        @if(!empty($audience_activity['hourly_activity']))
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-2">
                @foreach($audience_activity['hourly_activity'] as $hour)
                    <div class="text-center p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $hour['hour_formatted'] }}</p>
                        <div class="w-full h-12 bg-gray-200 dark:bg-gray-600 rounded mt-1 relative">
                            <div class="absolute bottom-0 w-full bg-purple-500 rounded" style="height: {{ min(100, $hour['activity_score'] / 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ number_format($hour['total_impressions']) }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No audience activity data.</p>
        @endif
    </div>
</div>
@endsection
