@extends('layouts.modern')

@section('title', 'System Health')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Health</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor the health of your application services.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($health as $service => $check)
            <div class="stat-card">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $check['status'] === 'ok' ? 'bg-green-100 dark:bg-green-900/30' : ($check['status'] === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-red-100 dark:bg-red-900/30') }}">
                        @if($check['status'] === 'ok')
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                        @elseif($check['status'] === 'warning')
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                        @else
                            <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white capitalize">{{ $service }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $check['message'] ?? 'Unknown' }}</p>
                        @if(isset($check['response_time_ms']))
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $check['response_time_ms'] }}ms</p>
                        @endif
                        @if(isset($check['pending_jobs']))
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Pending: {{ $check['pending_jobs'] }} | Failed: {{ $check['failed_jobs'] }}</p>
                        @endif
                    </div>
                    <span class="badge {{ $check['status'] === 'ok' ? 'badge-success' : ($check['status'] === 'warning' ? 'badge-warning' : 'badge-danger') }}">
                        {{ ucfirst($check['status']) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@endsection
