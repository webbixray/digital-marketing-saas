@extends('layouts.unified')
@section('title', 'Agent Dashboard')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="text-gray-900 font-medium">Agents</li>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/agent-dashboard.css') }}">
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4 mb-3">
 <div class="col-span-12">
 <div class="flex flex-wrap gap-2">
  <a href="{{ route('agents.dashboard') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
  <i class="fas fa-robot mr-1"></i> Agent Dashboard
  </a>
  <a href="{{ route('agents.workflows') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 inline-flex items-center gap-2 font-medium transition-colors">
  <i class="fas fa-project-diagram mr-1"></i> Workflows
  </a>
  <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="runAudit()">
  <i class="fas fa-shield-alt mr-1"></i> Run Audit
  </button>
  <button class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors" onclick="runImprovement()">
  <i class="fas fa-magic mr-1"></i> Run Improvement
  </button>
  <a href="#costSummary" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
  <i class="fas fa-dollar-sign mr-1"></i> View Costs
  </a>
 </div>
 </div>
</div>

<!-- System Health Score -->
<div class="grid grid-cols-12 gap-4><div class="col-span-12">
 <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
  <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-heartbeat mr-2"></i>System Health</h3>
  <div class="ml-auto">
     @php
            $overallStatus = $agentHealth['overall_status'] ?? 'healthy';
          @endphp
     <span class="badge badge-{{ $overallStatus === 'healthy' ? 'success' : ($overallStatus === 'degraded' ? 'warning' : 'danger') }} text-sm px-3 py-1">
      {{ ucfirst($overallStatus) }}
     </span>
    </div>
   </div>
   <div class="p-6">
    <div class="grid grid-cols-12 gap-4>
     <div class="md:col-span-3 text-center">
      <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
       <div class="bg-green-500 h-2 rounded-full bg-green-500 h-2 rounded-full-striped {{ ($agentHealth['system_score'] ?? 100) >= 95 ? 'bg-success' : (($agentHealth['system_score'] ?? 100) >= 80 ? 'bg-warning' : 'bg-danger') }}" 
         role="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700bar" 
         style="width: {{ $agentHealth['system_score'] ?? 100 }}%"
         aria-valuenow="{{ $agentHealth['system_score'] ?? 100 }}" 
         aria-valuemin="0" 
         aria-valuemax="100">
        {{ $agentHealth['system_score'] ?? 100 }}%
       </div>
      </div>
      <p class="mt-2 mb-0"><strong>Overall Health Score</strong></p>
     </div>
     <div class="md:col-span-3 text-center">
      <div class="text-center p-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20 agent-stat-success">
       <h2 class="text-green-600 dark:text-green-400 mb-0">{{ $agentHealth['healthy_agents'] ?? 0 }}</h2>
       <p class="text-gray-500 dark:text-gray-400">Healthy Agents</p>
      </div>
     </div>
     <div class="md:col-span-3 text-center">
      <div class="text-center p-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20 agent-stat-warning">
       <h2 class="text-yellow-600 dark:text-yellow-400 mb-0">{{ $agentHealth['degraded_agents'] ?? 0 }}</h2>
       <p class="text-gray-500 dark:text-gray-400">Degraded Agents</p>
      </div>
     </div>
     <div class="md:col-span-3 text-center">
      <div class="text-center p-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20 agent-stat-info">
       <h2 class="text-blue-600 dark:text-blue-400 mb-0">{{ $agentHealth['total_agents'] ?? 0 }}</h2>
       <p class="text-gray-500 dark:text-gray-400">Total Agents</p>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
</div>

<!-- Agent Cards Row -->
<div class="grid grid-cols-12 gap-4>
 @forelse($agents ?? [] as $name => $agent)
  @include('agents._agent-card', ['name' => $name, 'agent' => $agent])
 @empty
 <div class="col-span-12">
  <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4">
   <i class="fas fa-info-circle mr-2"></i> No agents registered yet. Agents will appear here once they are registered with the orchestrator.
  </div>
 </div>
 @endforelse
</div>

<!-- Cost Summary Row -->
<div class="grid grid-cols-12 gap-4 id="costSummary">
 <div class="col-span-12 md:col-span-6">
  <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 ">
   <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-chart-pie mr-2"></i>Cost Summary (This Month)</h3>
   </div>
   <div class="p-6">
    <div class="flex justify-content-between align-items-center mb-3">
     <span>Total AI Cost:</span>
     <span class="h4 text-indigo-600 dark:text-indigo-400">${{ number_format($costSummary['total'] ?? 0, 4) }}</span>
    </div>
    <hr>
    <h6>By Agent:</h6>
    <ul class="space-y-2">
     @forelse($costSummary['by_agent'] ?? [] as $agentName => $costData)
     <li class="flex justify-content-between align-items-center mb-2">
      <span>{{ ucwords(str_replace('_', ' ', $agentName)) }}</span>
      <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300">${{ number_format($costData['total_cost_usd'] ?? 0, 4) }}</span>
     </li>
     @empty
     <li class="text-gray-500 dark:text-gray-400">No costs recorded this month</li>
     @endforelse
    </ul>
   </div>
  </div>
 </div>
 <div class="col-span-12 md:col-span-6">
  <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
   <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-wallet mr-2"></i>Budget Status</h3>
   </div>
   <div class="p-6">
    <div class="flex justify-content-between align-items-center mb-3">
     <span>Budget Limit:</span>
     <span class="h4">${{ number_format($budgetLimit ?? 5.00, 2) }}</span>
    </div>
    <div class="flex justify-content-between align-items-center mb-3">
     <span>Remaining:</span>
     <span class="h4 text-{{ ($budgetRemaining ?? 5.00) < 1 ? 'danger' : 'success' }}">${{ number_format($budgetRemaining ?? 5.00, 2) }}</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
          @php
            $usedPercent = ($budgetLimit ?? 5) > 0 ? min(((($budgetLimit ?? 5) - ($budgetRemaining ?? 0)) / ($budgetLimit ?? 5)) * 100, 100) : 0;
          @endphp
     <div class="bg-green-500 h-2 rounded-full {{ $usedPercent > 80 ? 'bg-danger' : ($usedPercent > 50 ? 'bg-warning' : 'bg-success') }}" 
       style="width: {{ $usedPercent }}%">
      {{ number_format($usedPercent, 1) }}%
     </div>
    </div>
   </div>
  </div>
 </div>
</div>

<!-- Recent Activity Feed -->
<div class="grid grid-cols-12 gap-4>
 <div class="col-span-12">
  <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
   <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Recent Agent Activity</h3>
    <div class="ml-auto">
     <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">Last 10 executions</span>
    </div>
   </div>
   <div class="p-6 p-0">
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 odd:bg-gray-50 dark:odd:bg-gray-700/50">
     <thead>
      <tr>
       <th>Agent</th>
       <th>Task Type</th>
       <th>Status</th>
       <th>Cost</th>
       <th>Tokens</th>
       <th>Time</th>
      </tr>
     </thead>
     <tbody>
      @forelse($recentActivity ?? [] as $activity)
      <tr>
       <td>
        <span class="badge bg-gray-800 text-gray-100 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-900 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', $activity->agent_name ?? 'Unknown')) }}</span>
       </td>
       <td>{{ str_replace('_', ' ', $activity->task_type ?? 'N/A') }}</td>
       <td>
        @if($activity->cost_usd > 0)
         <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"><i class="fas fa-check mr-1"></i>Success</span>
        @else
         <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300"><i class="fas fa-minus mr-1"></i>Recorded</span>
        @endif
       </td>
       <td>${{ number_format($activity->cost_usd ?? 0, 6) }}</td>
       <td>{{ number_format($activity->tokens_used ?? 0) }}</td>
       <td>{{ $activity->executed_at ? \Carbon\Carbon::parse($activity->executed_at)->diffForHumans() : 'N/A' }}</td>
      </tr>
      @empty
      <tr>
       <td colspan="6" class="text-center text-gray-500 dark:text-gray-400 py-4">
        <i class="fas fa-inbox fa-2x mb-2"></i>
        <p>No recent agent activity recorded yet.</p>
       </td>
      </tr>
      @endforelse
     </tbody>
    </table></div>
   </div>
  </div>
 </div>
</div>
</div>
@endsection

@push('scripts')
<script>
 async function runAudit() {
  if (!confirm('Run a comprehensive security audit?')) return;
  
  try {
   const response = await dmsaas.request('{{ route(agents.dispatch) }}', {
    method: 'POST',
    body: JSON.stringify({
     agent_name: 'security_agent',
     task_type: 'security_audit',
     prompt: 'Run comprehensive security audit for agency'
    })
   });
   const data = await response.json();
   if (data.success) {
    dmsaas.toast('Security audit dispatched successfully!');
   } else {
    dmsaas.toast(data.message || 'Failed to dispatch audit.', 'error');
   }
  } catch (err) {
   dmsaas.toast('Network error. Please try again.', 'error');
  }
 }
</script>
@endpush
