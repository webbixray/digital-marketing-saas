@extends('layouts.unified')
@section('title', 'Report: {{ $report->name }}')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('reports.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Reports</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-white">{{ $report->name }}</span>
    </nav>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-lg">{{ $report->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <span class="badge badge-{{ $report->type === 'engagement' ? 'blue' : ($report->type === 'performance' ? 'green' : 'purple') }}">{{ ucfirst($report->type) }}</span>
                    <span class="ml-2">{{ $report->start_date }} &mdash; {{ $report->end_date }}</span>
                </p>
            </div>
            <a href="{{ route('reports.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Back to Reports</a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Impressions</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report->total_impressions ?? 0) }}</div>
                    <div class="text-xs text-green-600 dark:text-green-400 mt-1"><i class="fas fa-arrow-up"></i> +12.5%</div>
                </div>
                <div class="stat-card">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Engagement Rate</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report->engagement_rate ?? 0, 1) }}%</div>
                    <div class="text-xs text-green-600 dark:text-green-400 mt-1"><i class="fas fa-arrow-up"></i> +3.2%</div>
                </div>
                <div class="stat-card">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Conversions</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report->conversions ?? 0) }}</div>
                    <div class="text-xs text-red-600 dark:text-red-400 mt-1"><i class="fas fa-arrow-down"></i> -1.8%</div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-bar text-indigo-600 dark:text-indigo-400 text-2xl"></i>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Chart Visualization</h4>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Interactive charts will be displayed here using Chart.js. Data visualization coming soon.</p>
            </div>
            @if(!empty($report->platform))
            <div class="mt-6">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Platforms</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach((array) $report->platform as $platform)
                        <span class="badge badge-blue">{{ ucfirst($platform) }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
