@extends('layouts.unified')
@section('title', 'System Status')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Status</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor the health of your application services.</p>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Database -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Database</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $metrics['database']['driver'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Size: {{ $metrics['database']['size_mb'] ?? 'N/A' }} MB</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-database text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $metrics['cache']['driver'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Prefix: {{ $metrics['cache']['prefix'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-memory text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Storage</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $metrics['storage']['usage_percent'] }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $metrics['storage']['used_gb'] }} / {{ $metrics['storage']['total_gb'] }} GB</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-hdd text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $metrics['queue']['driver'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pending: {{ $metrics['queue']['pending_jobs'] }} | Failed: {{ $metrics['queue']['failed_jobs'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-list-ol text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Memory & PHP Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Memory -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Memory Usage</h3>
            </div>
            <div class="p-6">
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 mb-3">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, ($metrics['memory']['current_mb'] / ($metrics['php']['memory_limit'] == '-1' ? 512 : (int)$metrics['php']['memory_limit'])) * 100) }}%"></div>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Current:</strong> {{ $metrics['memory']['current_mb'] }} MB</p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Peak:</strong> {{ $metrics['memory']['peak_mb'] }} MB</p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Limit:</strong> {{ $metrics['php']['memory_limit'] }}</p>
            </div>
        </div>

        <!-- PHP -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">PHP Info</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Version:</strong> {{ $metrics['php']['version'] }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Max Execution Time:</strong> {{ $metrics['php']['max_execution_time'] }}s</p>
                <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Memory Limit:</strong> {{ $metrics['php']['memory_limit'] }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
