@extends("layouts.unified")

@section('title', 'Churn Predictions')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-user-times text-red-500 mr-2"></i>Churn Prediction
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Identify clients at risk of churning before they leave.</p>
        </div>
        <a href="{{ route('predictive.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- Threshold Selector -->
    <div class="stat-card mb-6">
        <form method="GET" action="{{ route('predictive.churn') }}" class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Risk Threshold:</label>
            <select name="threshold" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2" onchange="this.form.submit()">
                <option value="0.3" {{ $threshold == 0.3 ? 'selected' : '' }}>Low (≥0.3)</option>
                <option value="0.5" {{ $threshold == 0.5 ? 'selected' : '' }}>Medium (≥0.5)</option>
                <option value="0.7" {{ $threshold == 0.7 ? 'selected' : '' }}>High (≥0.7)</option>
                <option value="0.9" {{ $threshold == 0.9 ? 'selected' : '' }}>Critical (≥0.9)</option>
            </select>
            <span class="text-sm text-gray-500">{{ $predictions->count() }} clients at or above threshold</span>
        </form>
    </div>

    <!-- Predictions Table -->
    <div class="stat-card">
        @if($predictions->isEmpty())
            <div class="py-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">No clients above the churn risk threshold. Great job!</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Client</th>
                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Risk Score</th>
                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Risk Level</th>
                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Risk Factors</th>
                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($predictions as $prediction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $prediction['client_name'] }}</p>
                                    <p class="text-xs text-gray-500">ID: {{ $prediction['client_id'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold
                                            {{ $prediction['risk_score'] >= 0.8 ? 'text-red-600' :
                                               ($prediction['risk_score'] >= 0.6 ? 'text-orange-600' :
                                               ($prediction['risk_score'] >= 0.4 ? 'text-yellow-600' : 'text-green-600')) }}">
                                            {{ number_format($prediction['risk_score'], 2) }}
                                        </span>
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $prediction['risk_score'] >= 0.7 ? 'bg-red-500' : ($prediction['risk_score'] >= 0.4 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                                style="width: {{ $prediction['risk_score'] * 100 }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $prediction['risk_level'] === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                                           ($prediction['risk_level'] === 'high' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' :
                                           ($prediction['risk_level'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                           'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300')) }}">
                                        {{ ucfirst($prediction['risk_level']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if(!empty($prediction['risk_factors']))
                                        <ul class="space-y-1">
                                            @foreach($prediction['risk_factors'] as $factor)
                                                <li class="text-xs text-gray-600 dark:text-gray-400">
                                                    <i class="fas fa-circle text-[6px] mr-1 text-red-400"></i>
                                                    {{ $factor['description'] }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-xs text-gray-400">No significant factors</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ $prediction['recommendation'] }}
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Churn Trend -->
    <div class="stat-card mt-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-chart-line mr-2"></i>Churn Trend (Last {{ count($trends) }} months)
        </h3>
        @if(!empty($trends))
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($trends as $trend)
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $trend['month'] }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $trend['churn_rate'] }}%</p>
                        <p class="text-xs text-gray-500">{{ $trend['cancelled_count'] }} / {{ $trend['active_clients'] }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No historical churn data available.</p>
        @endif
    </div>
</div>
@endsection
