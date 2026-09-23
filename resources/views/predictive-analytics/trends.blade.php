@extends("layouts.unified")

@section('title', 'Emerging Trends')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-fire text-orange-500 mr-2"></i>Emerging Trends
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Detect rising topics, hashtags, and industry movements.</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('predictive.trends') }}" class="flex items-center gap-2">
                <select name="days" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>7 days</option>
                    <option value="14" {{ $days == 14 ? 'selected' : '' }}>14 days</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 days</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 days</option>
                </select>
            </form>
            <a href="{{ route('predictive.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Trend Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="stat-card">
            <p class="stat-label">Trending Items</p>
            <p class="stat-value">{{ count($detected['trending'] ?? []) }}</p>
            <p class="text-xs text-gray-500 mt-1">Above growth threshold</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Overall Velocity</p>
            <p class="stat-value {{ ($detected['overall_velocity'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $detected['overall_velocity'] ?? 0 }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Engagement trend</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Industry Direction</p>
            <p class="stat-value text-sm capitalize">{{ str_replace('_', ' ', $industry['trend_direction'] ?? 'stable') }}</p>
            <p class="text-xs text-gray-500 mt-1">Market movement</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Emerging Topics -->
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Emerging Topics
            </h3>
            @if(!empty($topics))
                <div class="space-y-3">
                    @foreach($topics as $topic)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $topic['topic'] }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $topic['current_count'] }} mentions
                                    @if($topic['previous_count'] > 0)
                                        (was {{ $topic['previous_count'] }})
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold
                                    {{ $topic['growth_rate'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $topic['growth_rate'] > 0 ? '+' : '' }}{{ number_format($topic['growth_rate'] * 100, 1) }}%
                                </span>
                                <p class="text-xs text-gray-500">Conf: {{ $topic['confidence'] * 100 }}%</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No topic data available.</p>
            @endif
        </div>

        <!-- Hashtag Trends -->
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-hashtag text-blue-500 mr-2"></i>Trending Hashtags
            </h3>
            @if(!empty($hashtags))
                <div class="space-y-3">
                    @foreach($hashtags as $tag)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div>
                                <p class="font-medium text-blue-600 dark:text-blue-400">{{ $tag['hashtag'] }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $tag['current_usage'] }} uses this week
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold
                                    {{ $tag['growth_rate'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $tag['growth_rate'] > 0 ? '+' : '' }}{{ number_format($tag['growth_rate'] * 100, 1) }}%
                                </span>
                                <p class="text-xs text-gray-500">Conf: {{ $tag['confidence'] * 100 }}%</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No hashtag data available.</p>
            @endif
        </div>
    </div>

    <!-- Competitor Trends -->
    @if(!empty($competitor['trends']))
        <div class="stat-card mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-binoculars text-purple-500 mr-2"></i>Social Listening Insights
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Keyword</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Platform</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Mentions</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Sentiment</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Opportunity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($competitor['trends'] as $comp)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $comp['keyword'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ ucfirst($comp['platform']) }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $comp['mentions'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $comp['sentiment_score'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $comp['sentiment_score'] >= 0 ? '+' : '' }}{{ $comp['sentiment_score'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-blue-600 font-semibold">{{ $comp['opportunity_score'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Industry Overview -->
    <div class="stat-card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-industry text-gray-500 mr-2"></i>Industry Overview
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top Tags</p>
                @if(!empty($industry['top_tags']))
                    <div class="flex flex-wrap gap-2">
                        @foreach(array_slice($industry['top_tags'], 0, 10, true) as $tag => $count)
                            <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                                {{ $tag }} <span class="text-gray-400">({{ $count }})</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top Hashtags</p>
                @if(!empty($industry['top_hashtags']))
                    <div class="flex flex-wrap gap-2">
                        @foreach(array_slice($industry['top_hashtags'], 0, 10, true) as $tag => $count)
                            <span class="px-2 py-1 text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-full">
                                #{{ $tag }} <span class="text-blue-400">({{ $count }})</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
