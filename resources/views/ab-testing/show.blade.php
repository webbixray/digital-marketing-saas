@extends('layouts.unified')
@section('title', $test->name)

@section('content')

<x-flash-messages />

<!-- Breadcrumb -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white m-0">{{ $test->name }}</h1>
    </div>
    <div class="flex items-center justify-start sm:justify-end">
        <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400">
            <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('ab-testing.index') }}" class="hover:text-indigo-600">A/B Testing</a></li>
            <li>/</li>
            <li class="text-gray-900 dark:text-white font-medium">{{ $test->name }}</li>
        </ol>
    </div>
</div>

<!-- Status Banner -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            @php
                $statusConfig = [
                    'draft' => ['icon' => 'fa-file-alt', 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'],
                    'running' => ['icon' => 'fa-play', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'],
                    'paused' => ['icon' => 'fa-pause', 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'],
                    'completed' => ['icon' => 'fa-check', 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'],
                ];
                $config = $statusConfig[$test->status] ?? $statusConfig['draft'];
            @endphp
            <i class="fas {{ $config['icon'] }} text-lg"></i>
            <span class="{{ $config['color'] }} text-sm font-medium px-3 py-1 rounded-full">{{ ucfirst($test->status) }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($test->type) }} | {{ ucfirst($test->platform) }}</span>
        </div>
        <div class="flex items-center gap-2">
            @if($test->status === 'draft')
            <form action="{{ route('ab-testing.start', $test) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors text-sm">
                    <i class="fas fa-play"></i>Start Test
                </button>
            </form>
            @endif
            @if($test->status === 'running')
            <form action="{{ route('ab-testing.pause', $test) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors text-sm">
                    <i class="fas fa-pause"></i>Pause
                </button>
            </form>
            @endif
            @if(in_array($test->status, ['running', 'paused']))
            <form action="{{ route('ab-testing.complete', $test) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-sm">
                    <i class="fas fa-check"></i>Complete
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

<!-- Hypothesis -->
@if($test->hypothesis)
<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
    <p class="text-sm text-blue-800 dark:text-blue-300"><strong><i class="fas fa-lightbulb mr-1"></i>Hypothesis:</strong> {{ $test->hypothesis }}</p>
</div>
@endif

<!-- Winner Banner -->
@if($test->winner)
<div class="rounded-xl p-4 mb-6 {{ $test->winner === 'inconclusive' ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' }}">
    <div class="flex items-center gap-3">
        @if($test->winner === 'inconclusive')
            <i class="fas fa-question-circle text-yellow-600 dark:text-yellow-400 text-2xl"></i>
            <div>
                <p class="font-semibold text-yellow-800 dark:text-yellow-300">Results Inconclusive</p>
                <p class="text-sm text-yellow-700 dark:text-yellow-400">No statistically significant difference detected between variants.</p>
            </div>
        @else
            <i class="fas fa-trophy text-green-600 dark:text-green-400 text-2xl"></i>
            <div>
                <p class="font-semibold text-green-800 dark:text-green-300">Winner: Variant {{ strtoupper($test->winner) }}</p>
                <p class="text-sm text-green-700 dark:text-green-400">{{ number_format($test->confidence, 1) }}% confidence level{{ $analysis['significant'] ? ' (Statistically Significant)' : '' }}</p>
            </div>
        @endif
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Statistical Analysis -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-chart-bar mr-2"></i>Statistical Analysis</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Chi-Squared</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($analysis['chi_squared'], 2) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">P-Value</p>
                        <p class="text-lg font-bold {{ $analysis['p_value'] < 0.05 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">{{ number_format($analysis['p_value'], 4) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Effect Size</p>
                        <p class="text-lg font-bold {{ $analysis['effect_size'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">{{ ($analysis['effect_size'] > 0 ? '+' : '') . number_format($analysis['effect_size'], 1) }}%</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Significant</p>
                        <p class="text-lg font-bold {{ $analysis['significant'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">{{ $analysis['significant'] ? 'Yes' : 'No' }}</p>
                    </div>
                </div>

                <!-- Recommendation -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                    <p class="text-sm text-indigo-800 dark:text-indigo-300"><strong><i class="fas fa-robot mr-1"></i>Recommendation:</strong> {{ $analysis['recommendation'] }}</p>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-chart-line mr-2"></i>Performance Comparison</h3>
            </div>
            <div class="p-6">
                <canvas id="abTestChart" height="200"></canvas>
            </div>
        </div>

        <!-- Variants -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Variant A -->
            <div class="bg-white rounded-xl shadow-md border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold">A</span>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Control</h3>
                    </div>
                    @if($test->winner === 'a')
                    <span class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium px-2 py-0.5 rounded-full"><i class="fas fa-crown mr-1"></i>Winner</span>
                    @endif
                </div>
                <div class="p-5">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $test->variant_a_content }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($test->variant_a_impressions) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Impressions</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($test->variant_a_engagement) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Engagement</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($test->variant_a_clicks) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Clicks</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-3 text-center">
                        <div>
                            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($analysis['a_engagement_rate'], 1) }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Engagement Rate</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($analysis['a_ctr'], 1) }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">CTR</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variant B -->
            <div class="bg-white rounded-xl shadow-md border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
                <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 flex items-center justify-center text-sm font-bold">B</span>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Treatment</h3>
                    </div>
                    @if($test->winner === 'b')
                    <span class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium px-2 py-0.5 rounded-full"><i class="fas fa-crown mr-1"></i>Winner</span>
                    @endif
                </div>
                <div class="p-5">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $test->variant_b_content }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($test->variant_b_impressions) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Impressions</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($test->variant_b_engagement) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Engagement</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($test->variant_b_clicks) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Clicks</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-3 text-center">
                        <div>
                            <p class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($analysis['b_engagement_rate'], 1) }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Engagement Rate</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($analysis['b_ctr'], 1) }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">CTR</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Details -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Test Details</h3>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Platform</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($test->platform) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Type</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($test->type) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Sample Size</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $test->sample_size }}/variant</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Account</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $test->socialAccount?->platform_username ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Created</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $test->created_at->format('M d, Y') }}</span>
                </div>
                @if($test->started_at)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Started</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $test->started_at->format('M d, Y') }}</span>
                </div>
                @endif
                @if($test->ended_at)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Ended</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $test->ended_at->format('M d, Y') }}</span>
                </div>
                @endif
                @if($analysis['duration_hours'])
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Duration</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $analysis['duration_hours'] }} hours</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Progress -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Sample Progress</h3>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <span>Variant A</span>
                        <span>{{ $test->variant_a_impressions }}/{{ $test->sample_size }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, ($test->variant_a_impressions / max(1, $test->sample_size)) * 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <span>Variant B</span>
                        <span>{{ $test->variant_b_impressions }}/{{ $test->sample_size }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ min(100, ($test->variant_b_impressions / max(1, $test->sample_size)) * 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Significance Scale -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Confidence Scale</h3>
            </div>
            <div class="p-5">
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">≥95%: Significant</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">90-95%: Marginal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-gray-700 dark:text-gray-300">&lt;90%: Not significant</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('abTestChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Impressions', 'Engagement', 'Clicks'],
                datasets: [
                    {
                        label: 'Variant A',
                        data: [{{ $test->variant_a_impressions }}, {{ $test->variant_a_engagement }}, {{ $test->variant_a_clicks }}],
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 1
                    },
                    {
                        label: 'Variant B',
                        data: [{{ $test->variant_b_impressions }}, {{ $test->variant_b_engagement }}, {{ $test->variant_b_clicks }}],
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
