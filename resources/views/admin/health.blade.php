@extends("layouts.unified")

@section('title', 'System Health')

@section('content')
<x-flash-messages />
<div class="mb-8" x-data="{
    lastChecked: '{{ now()->toDateTimeString() }}',
    loading: false,
    refresh() {
        this.loading = true;
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(() => {
                this.lastChecked = new Date().toLocaleString();
                this.loading = false;
            });
    },
    init() {
        setInterval(() => this.refresh(), 60000);
    }
}">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Health</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor the health of your application services.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 dark:text-gray-400">Last checked: <span x-text="lastChecked"></span></span>
            <button @click="refresh()" :disabled="loading" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-1 text-sm font-medium transition-colors text-gray-700 dark:text-gray-200 disabled:opacity-50">
                <i class="fas fa-sync" :class="{ 'fa-spin': loading }"></i> Refresh
            </button>
        </div>
    </div>

    @php
        $allOk = collect($health)->every(fn($check) => $check['status'] === 'ok');
        $anyError = collect($health)->contains(fn($check) => $check['status'] === 'error');
    @endphp

    @if($allOk)
        <div class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
            <span class="text-green-700 dark:text-green-300 font-medium">All systems operational</span>
        </div>
    @elseif($anyError)
        <div class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 flex items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400"></i>
            <span class="text-red-700 dark:text-red-300 font-medium">Some systems are experiencing issues</span>
        </div>
    @else
        <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 flex items-center gap-2" role="alert">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
            <span class="text-yellow-700 dark:text-yellow-300 font-medium">Some systems have warnings</span>
        </div>
    @endif
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
   <x-badge :variant="$check['status'] === 'ok' ? 'success' : ($check['status'] === 'warning' ? 'warning' : 'danger')" :text="ucfirst($check['status'])" />
  </div>
  </div>
 @endforeach
</div>
@endsection
