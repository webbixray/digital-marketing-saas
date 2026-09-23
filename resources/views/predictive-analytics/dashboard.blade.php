@extends("layouts.unified")

@section('title', 'Predictive Analytics')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-brain text-purple-500 mr-2"></i>Predictive Analytics 2.0
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">AI-powered insights to predict churn, revenue, and trends.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('predictive.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-purple-600 text-white">
                Dashboard
            </a>
            <a href="{{ route('predictive.churn') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                Churn
            </a>
            <a href="{{ route('predictive.revenue') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                Revenue
            </a>
            <a href="{{ route('predictive.trends') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                Trends
            </a>
            <a href="{{ route('predictive.optimal-times') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                Optimal Times
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- High Risk Clients -->
        <div class="stat-card border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">High Risk Clients</p>
                    <p class="stat-value text-red-600">{{ $churn['high_risk_count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Threshold ≥ 0.7</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Current MRR -->
        <div class="stat-card border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Current MRR</p>
                    <p class="stat-value text-green-600">${{ number_format($revenue['forecast']['current_mrr'] ?? 0, 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Monthly Recurring</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Projected Growth -->
        <div class="stat-card border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Projected Growth</p>
                    <p class="stat-value text-blue-600">{{ $revenue['forecast']['projected_growth'] ?? 0 }}%</p>
                    <p class="text-xs text-gray-500 mt-1">Next 3 months</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Trending Topics -->
        <div class="stat-card border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Trending Topics</p>
                    <p class="stat-value text-purple-600">{{ count($trends['topics']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Emerging in last 30 days</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-fire text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Churn Risk Summary -->
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-user-times text-red-500 mr-2"></i>Churn Risk Summary
            </h3>
            @if($churn['high_risk_count'] > 0)
                <div class="space-y-3">
                    @foreach($churn['high_risk_clients']->take(5) as $client)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $client->name }}</p>
                                <p class="text-xs text-gray-500">Score: {{ number_format($client->churn_risk_score, 2) }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $client->risk_level === 'critical' ? 'bg-red-100 text-red-800' :
                                   ($client->risk_level === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($client->risk_level) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm py-4 text-center">
                    <i class="fas fa-check-circle text-green-500 mr-1"></i>No high-risk clients detected.
                </p>
            @endif
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Churn rate (6mo avg)</span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ count($churn['trends']) > 0 ? number_format(collect($churn['trends'])->avg('churn_rate'), 1) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Revenue Forecast Chart -->
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-chart-area text-green-500 mr-2"></i>Revenue Forecast
            </h3>
            @if(!empty($revenue['forecast']['forecast']))
                <div class="space-y-3">
                    @foreach($revenue['forecast']['forecast'] as $month)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $month['month'] }}</p>
                                <div class="w-48 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ min(100, ($month['projected_revenue'] / max($revenue['forecast']['current_mrr'], 1)) * 80) }}%"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($month['projected_revenue'], 0) }}</p>
                                <p class="text-xs text-gray-500">Conf: {{ $month['confidence'] * 100 }}%</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm py-4 text-center">No revenue data available yet.</p>
            @endif
        </div>
    </div>

    <!-- Trend Alerts -->
    <div class="stat-card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-bolt text-yellow-500 mr-2"></i>Trend Alerts
        </h3>
        @if(!empty($trends['summary']['trending']))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach(array_slice($trends['summary']['trending'], 0, 6) as $trend)
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center
                            {{ $trend['type'] === 'hashtag' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }}">
                            <i class="fas {{ $trend['type'] === 'hashtag' ? 'fa-hashtag' : 'fa-tag' }}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $trend['name'] }}
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400">
                                <i class="fas fa-arrow-up"></i>{{ number_format($trend['growth_rate'] * 100, 1) }}%
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm py-4 text-center">
                <i class="fas fa-info-circle mr-1"></i>Not enough data to detect trends yet.
            </p>
        @endif
    </div>
</div>
@endsection
