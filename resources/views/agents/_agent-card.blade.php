@php
  $status = $agent['status'] ?? 'active';
  $statusClass = $status === 'active' ? '' : ($status === 'warning' ? '' : '');
  $badgeClass = $status === 'active' ? 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300' : ($status === 'warning' ? 'bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300');
  $statusIcon = $status === 'active' ? 'fa-check-circle' : ($status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
@endphp

<div class="lg:col-span-4 md:col-span-6 mb-3">
 <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 agent-card">
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
   <h3 class="font-semibold text-gray-900 dark:text-white">
    <i class="fas fa-robot mr-2"></i>{{ ucwords(str_replace('_', ' ', $name)) }}
   </h3>
   <div class="ml-auto">
    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300 {{ $badgeClass }} agent-status-badge" data-status="{{ $status }}">
     <i class="fas {{ $statusIcon }} mr-1"></i>{{ ucfirst($status) }}
    </span>
   </div>
  </div>
  <div class="p-6">
   <div class="grid grid-cols-12 gap-4>
    <div class="col-span-6">
     <div class="text-center border-r border-gray-200 dark:border-gray-700">
      <h5 class="text-lg font-semibold text-gray-900 dark:text-white text-{{ ($agent['success_rate'] ?? 0) >= 0.8 ? 'success' : (($agent['success_rate'] ?? 0) >= 0.5 ? 'warning' : 'danger') }}">
       {{ number_format(($agent['success_rate'] ?? 0) * 100, 1) }}%
      </h5>
      <span class="text-sm text-gray-500 dark:text-gray-400">Success Rate</span>
     </div>
    </div>
    <div class="col-span-6">
     <div class="text-center">
      <h5 class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($agent['total_executed'] ?? 0) }}</h5>
      <span class="text-sm text-gray-500 dark:text-gray-400">Total Executions</span>
     </div>
    </div>
   </div>
   <div class="mt-3">
    <div class="col-span-6">
     <div class="text-center border-r border-gray-200 dark:border-gray-700">
      <h5 class="text-lg font-semibold text-gray-900 dark:text-white text-blue-600 dark:text-blue-400">{{ number_format($agent['total_successes'] ?? 0) }}</h5>
      <span class="text-sm text-gray-500 dark:text-gray-400">Successful</span>
     </div>
    </div>
    <div class="col-span-6">
     <div class="text-center">
      <h5 class="text-lg font-semibold text-gray-900 dark:text-white text-indigo-600 dark:text-indigo-400">${{ number_format($agent['avg_cost_per_task'] ?? 0, 4) }}</h5>
      <span class="text-sm text-gray-500 dark:text-gray-400">Avg Cost/Task</span>
     </div>
    </div>
   </div>
   <div class="mt-3">
    <small class="text-gray-500 dark:text-gray-400">
     <i class="fas fa-clock mr-1"></i>
     Last run: {{ $agent['last_run'] ?? 'Never' }}
    </small>
    <br>
    <small class="text-gray-500 dark:text-gray-400">
     <i class="fas fa-dollar-sign mr-1"></i>
     Total cost: ${{ number_format($agent['total_cost'] ?? 0, 4) }}
    </small>
    <br>
    <small class="text-gray-500 dark:text-gray-400">
     <i class="fas fa-layer-group mr-1"></i>
     Category: <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $agent['category'] ?? 'General' }}</span>
    </small>
   </div>
   @if(!empty($agent['description']))
   <div class="mt-3">
    <p class="text-gray-500 dark:text-gray-400 small mb-0">{{ $agent['description'] }}</p>
   </div>
   @endif
   <div class="mt-3">
    <span class="text-gray-500 dark:text-gray-400 small">Supported Tasks:</span>
    <div class="mt-1">
     @foreach(array_slice($agent['supported_types'] ?? [], 0, 4) as $type)
      <span class="badge bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $type) }}</span>
     @endforeach
     @if(count($agent['supported_types'] ?? []) > 4)
      <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">+{{ count($agent['supported_types']) - 4 }}</span>
     @endif
    </div>
   </div>
  </div>
  <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
   <a href="{{ route('agents.show', $name) }}" class="btn px-3 py-1.5 text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
    <i class="fas fa-eye mr-1"></i> View Details
   </a>
   <button class="btn px-3 py-1.5 text-sm bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="dispatchTask('{{ $name }}')">
    <i class="fas fa-paper-plane mr-1"></i> Dispatch
   </button>
  </div>
 </div>
</div>
