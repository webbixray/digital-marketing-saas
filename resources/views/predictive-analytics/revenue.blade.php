@extends("layouts.unified")

@section('title', 'Revenue Forecast')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-chart-line text-green-500 mr-2"></i>Revenue Forecast
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Predict future revenue based on growth trends and churn rates.</p>
        </div>
        <a href="{{ route('predictive.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card">
            <p class="stat-label">Current MRR</p>
            <p class="stat-value">${{ number_format($mrr['current_mrr'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $mrr['average_growth_rate'] }}% avg monthly growth</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Current ARR</p>
            <p class="stat-value">${{ number_format($arr['current_arr'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Annual run rate</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Projected Growth (3mo)</p>
            <p class="stat-value {{ ($forecast['projected_growth'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $forecast['projected_growth'] ?? 0 }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Based on historical data</p>
        </div>
    </div>

    <!-- Revenue Forecast -->
    <div class="stat-card mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-chart-area text-green-500 mr-2"></i>Revenue Forecast (Next {{ count($forecast['forecast'] ?? []) }} Months)
        </h3>
        @if(!empty($forecast['forecast']))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Month</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Projected Revenue</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Growth Rate</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Churn Rate</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Confidence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($forecast['forecast'] as $month)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $month['month'] }}</td>
                                <td class="px-4 py-3 text-green-600 font-semibold">${{ number_format($month['projected_revenue'], 0) }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $month['growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $month['growth_rate'] }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-red-600">{{ $month['churn_rate'] }}%</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $month['confidence'] * 100 }}%"></div>
                                        </div>
                                        <span class="text-xs">{{ $month['confidence'] * 100 }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No forecast data available.</p>
        @endif
    </div>

    <!-- MRR and ARR Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-dollar-sign text-blue-500 mr-2"></i>MRR Projection (12 Months)
            </h3>
            @if(!empty($mrr['next_12_months']))
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach(array_slice($mrr['next_12_months'], 0, 12) as $m)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400 w-24">{{ $m['month'] }}</span>
                            <div class="flex-1 mx-3 bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-blue-500 h-3 rounded-full" style="width: {{ min(100, ($m['mrr'] / max($mrr['current_mrr'], 1)) * 70) }}%"></div>
                            </div>
                            <span class="text-gray-900 dark:text-white font-medium w-20 text-right">${{ number_format($m['mrr'], 0) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="stat-card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>ARR Projection (12 Months)
            </h3>
            @if(!empty($arr['next_12_months']))
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach(array_slice($arr['next_12_months'], 0, 12) as $a)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400 w-24">{{ $a['month'] }}</span>
                            <div class="flex-1 mx-3 bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-purple-500 h-3 rounded-full" style="width: {{ min(100, ($a['arr'] / max($arr['current_arr'], 1)) * 70) }}%"></div>
                            </div>
                            <span class="text-gray-900 dark:text-white font-medium w-24 text-right">${{ number_format($a['arr'] / 1000, 1) }}k</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Plan Distribution -->
    <div class="stat-card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-layer-group text-indigo-500 mr-2"></i>Plan Distribution
        </h3>
        @if(!empty($plan_distribution['distribution']))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($plan_distribution['distribution'] as $plan)
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($plan['plan_name']) }}</span>
                            <span class="text-sm text-gray-500">{{ $plan['client_count'] }} clients</span>
                        </div>
                        <p class="text-lg font-bold text-green-600">${{ number_format($plan['total_revenue'], 2) }}</p>
                        <p class="text-xs text-gray-500">Monthly revenue</p>
                        @if($plan_distribution['total_clients'] > 0)
                            <div class="mt-2 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ ($plan['client_count'] / $plan_distribution['total_clients']) * 100 }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between text-sm">
                <span class="text-gray-500">Total clients: {{ $plan_distribution['total_clients'] }}</span>
                <span class="text-gray-500">Total revenue: ${{ number_format($plan_distribution['total_revenue'], 2) }}</span>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No plan distribution data available.</p>
        @endif
    </div>
</div>
@endsection
