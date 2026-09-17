@extends('layouts.unified')
@section('title', 'System Status')

@section('content')
<x-flash-messages />
<div class="space-y-6">

</div>


        <div class="grid grid-cols-12 gap-4>
            <!-- Database -->
            <div class="col-span-12 md:col-span-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Database</span>
                        <span class="info-box-number">{{ $metrics['database']['driver'] }}</span>
                        <span class="progress-description">
                            Size: {{ $metrics['database']['size_mb'] ?? 'N/A' }} MB
                        </span>
                    </div>
                </div>
            </div>

            <!-- Cache -->
            <div class="col-span-12 md:col-span-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-memory"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cache</span>
                        <span class="info-box-number">{{ $metrics['cache']['driver'] }}</span>
                        <span class="progress-description">
                            Prefix: {{ $metrics['cache']['prefix'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Storage -->
            <div class="col-span-12 md:col-span-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-hdd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Storage</span>
                        <span class="info-box-number">{{ $metrics['storage']['usage_percent'] }}%</span>
                        <span class="progress-description">
                            {{ $metrics['storage']['used_gb'] }} / {{ $metrics['storage']['total_gb'] }} GB
                        </span>
                    </div>
                </div>
            </div>

            <!-- Queue -->
            <div class="col-span-12 md:col-span-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-list-ol"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Queue</span>
                        <span class="info-box-number">{{ $metrics['queue']['driver'] }}</span>
                        <span class="progress-description">
                            Pending: {{ $metrics['queue']['pending_jobs'] }} | Failed: {{ $metrics['queue']['failed_jobs'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4>
            <!-- Memory -->
            <div class="col-span-12 md:col-span-6">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Memory Usage</h3>
                    </div>
                    <div class="p-6">
                        <div class="progress mb-3">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, ($metrics['memory']['current_mb'] / ($metrics['php']['memory_limit'] == '-1' ? 512 : (int)$metrics['php']['memory_limit'])) * 100) }}%">
                                {{ $metrics['memory']['current_mb'] }} MB
                            </div>
                        </div>
                        <p><strong>Peak:</strong> {{ $metrics['memory']['peak_mb'] }} MB</p>
                        <p><strong>Limit:</strong> {{ $metrics['php']['memory_limit'] }}</p>
                    </div>
                </div>
            </div>

            <!-- PHP -->
            <div class="col-span-12 md:col-span-6">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">PHP Info</h3>
                    </div>
                    <div class="p-6">
                        <p><strong>Version:</strong> {{ $metrics['php']['version'] }}</p>
                        <p><strong>Max Execution Time:</strong> {{ $metrics['php']['max_execution_time'] }}s</p>
                        <p><strong>Memory Limit:</strong> {{ $metrics['php']['memory_limit'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

