@extends('layouts.app')
@section('title', 'System Status')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">System Status</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">System Status</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Database -->
            <div class="col-md-3">
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
            <div class="col-md-3">
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
            <div class="col-md-3">
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
            <div class="col-md-3">
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

        <div class="row">
            <!-- Memory -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Memory Usage</h3>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, ($metrics['memory']['current_mb'] / ($metrics['php']['memory_limit'] == '-1' ? 512 : (int)$metrics['php']['memory_limit'])) * 100) }}%">
                                {{ $metrics['memory']['current_mb'] }} MB
                            </div>
                        </div>
                        <p><strong>Peak:</strong> {{ $metrics['memory']['peak_mb'] }} MB</p>
                        <p><strong>Limit:</strong> {{ $metrics['php']['memory_limit'] }}</p>
                    </div>
                </div>
            </div>

            <!-- PHP -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">PHP Info</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Version:</strong> {{ $metrics['php']['version'] }}</p>
                        <p><strong>Max Execution Time:</strong> {{ $metrics['php']['max_execution_time'] }}s</p>
                        <p><strong>Memory Limit:</strong> {{ $metrics['php']['memory_limit'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
