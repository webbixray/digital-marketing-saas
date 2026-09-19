@extends('layouts.unified')
@section('title', 'Agent Dashboard')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="text-gray-900 font-medium">Agents</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Dashboard</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor AI agent health, costs, and activity.</p>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
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

    <!-- System Health Score -->
    <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-heartbeat mr-2"></i>System Health</h3>
            @php
                $overallStatus = $agentHealth['overall_status'] ?? 'healthy';
            @endphp
            <span class="px-2.5 py-0.5 text-sm font-medium rounded-full {{ $overallStatus === 'healthy' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($overallStatus === 'degraded' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">
                {{ ucfirst($overallStatus) }}
            </span>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="md:col-span-2 lg:col-span-1 text-center">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="h-2.5 rounded-full {{ ($agentHealth['system_score'] ?? 100) >= 95 ? 'bg-green-500' : (($agentHealth['system_score'] ?? 100) >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                            role="progressbar" 
                            style="width: {{ $agentHealth['system_score'] ?? 100 }}%"
                            aria-valuenow="{{ $agentHealth['system_score'] ?? 100 }}" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                        </div>
                    </div>
                    <p class="mt-2"><strong>{{ $agentHealth['system_score'] ?? 100 }}% Overall Health Score</strong></p>
                </div>
                <div class="text-center">
                    <div class="p-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20">
                        <h2 class="text-green-600 dark:text-green-400">{{ $agentHealth['healthy_agents'] ?? 0 }}</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Healthy Agents</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="p-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20">
                        <h2 class="text-yellow-600 dark:text-yellow-400">{{ $agentHealth['degraded_agents'] ?? 0 }}</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Degraded Agents</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="p-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20">
                        <h2 class="text-blue-600 dark:text-blue-400">{{ $agentHealth['total_agents'] ?? 0 }}</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Total Agents</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($agents ?? [] as $name => $agent)
            @include('agents._agent-card', ['name' => $name, 'agent' => $agent])
        @empty
            <div class="md:col-span-2 lg:col-span-3">
                <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4">
                    <i class="fas fa-info-circle mr-2"></i> No agents registered yet. Agents will appear here once they are registered with the orchestrator.
                </div>
            </div>
        @endforelse
    </div>

    <!-- Cost Summary Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="costSummary">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-chart-pie mr-2"></i>Cost Summary (This Month)</h3>
            </div>
            <div class="p-6">
                <div class="flex justify-between items-center mb-3">
                    <span>Total AI Cost:</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold">${{ number_format($costSummary['total'] ?? 0, 4) }}</span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700 my-3">
                <h6 class="font-medium text-gray-700 dark:text-gray-300 mb-2">By Agent:</h6>
                <ul class="space-y-2">
                    @forelse($costSummary['by_agent'] ?? [] as $agentName => $costData)
                    <li class="flex justify-between items-center">
                        <span>{{ ucwords(str_replace('_', ' ', $agentName)) }}</span>
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300">${{ number_format($costData['total_cost_usd'] ?? 0, 4) }}</span>
                    </li>
                    @empty
                    <li class="text-gray-500 dark:text-gray-400">No costs recorded this month</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-wallet mr-2"></i>Budget Status</h3>
            </div>
            <div class="p-6">
                <div class="flex justify-between items-center mb-3">
                    <span>Budget Limit:</span>
                    <span class="font-semibold">${{ number_format($budgetLimit ?? 5.00, 2) }}</span>
                </div>
                <div class="flex justify-between items-center mb-3">
                    <span>Remaining:</span>
                    <span class="font-semibold {{ ($budgetRemaining ?? 5.00) < 1 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">${{ number_format($budgetRemaining ?? 5.00, 2) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    @php
                        $usedPercent = ($budgetLimit ?? 5) > 0 ? min(((($budgetLimit ?? 5) - ($budgetRemaining ?? 0)) / ($budgetLimit ?? 5)) * 100, 100) : 0;
                    @endphp
                    <div class="h-2 rounded-full {{ $usedPercent > 80 ? 'bg-red-500' : ($usedPercent > 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                        style="width: {{ $usedPercent }}%">
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($usedPercent, 1) }}% used</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Recent Agent Activity</h3>
            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">Last 10 executions</span>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cost</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tokens</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentActivity ?? [] as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $activity->agent_name ?? 'Unknown')) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $activity->task_type ?? 'N/A') }}</td>
                            <td class="px-4 py-3">
                                @if($activity->cost_usd > 0)
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"><i class="fas fa-check mr-1"></i>Success</span>
                                @else
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300"><i class="fas fa-minus mr-1"></i>Recorded</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${{ number_format($activity->cost_usd ?? 0, 6) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ number_format($activity->tokens_used ?? 0) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $activity->executed_at ? \Carbon\Carbon::parse($activity->executed_at)->diffForHumans() : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p>No recent agent activity recorded yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
 async function runAudit() {
  if (!confirm('Run a comprehensive security audit?')) return;
  
  try {
   const response = await dmsaas.request('{{ route('agents.dispatch') }}', {
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
