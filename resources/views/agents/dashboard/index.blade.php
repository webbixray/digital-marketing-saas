@extends('layouts.unified')
@section('title', 'AI Agent Orchestration Dashboard')
@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="text-gray-900 font-medium">Agent Orchestration</li>
@endsection

@section('head-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js" nonce="{{ $cspNonce ?? '' }}"></script>
@endsection

@section('content')
<x-flash-messages />

<div x-data="agentDashboard()" x-init="initCharts()" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-network-wired text-indigo-500"></i>
                AI Agent Orchestration Dashboard
                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300">v6.0</span>
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Real-time agent status, AI cost spend, learning & collaboration.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="lastUpdated"></span>
            <button @click="refreshData()" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Idle -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Idle Agents</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $statusCounts['idle'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <i class="fas fa-bed text-gray-500 text-xl"></i>
                </div>
            </div>
        </div>
        <!-- Running -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Running Agents</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $statusCounts['running'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>
        <!-- Error -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Error Agents</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $statusCounts['error'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Status Cards -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-robot mr-2 text-indigo-500"></i>Agent Fleet Status
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($agents ?? [] as $name => $agent)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            @php
                                $status = $agent['status'] ?? 'idle';
                                $statusColors = [
                                    'idle' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'error' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                ];
                                $statusIcons = ['idle' => 'fa-bed', 'running' => 'fa-spinner fa-spin', 'error' => 'fa-exclamation-circle'];
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$status] }}">
                                <i class="fas {{ $statusIcons[$status] }} mr-1"></i>{{ ucfirst($status) }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-layer-group mr-1"></i>{{ $agent['category'] ?? 'General' }}
                        </span>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                        {{ ucwords(str_replace('_', ' ', $name)) }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $agent['description'] ?? '' }}</p>

                    <!-- Success Rate -->
                    <div class="mb-2">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Success Rate</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format(($agent['success_rate'] ?? 0) * 100, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full {{ ($agent['success_rate'] ?? 0) >= 0.8 ? 'bg-green-500' : (($agent['success_rate'] ?? 0) >= 0.5 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                 style="width: {{ ($agent['success_rate'] ?? 0) * 100 }}%"></div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-2 text-center pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Runs</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($agent['total_executed'] ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Avg Cost</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($agent['avg_cost_per_task'] ?? 0, 4) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Cost</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($agent['total_cost'] ?? 0, 4) }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-clock mr-1"></i>Last run: {{ $agent['last_run'] ?? 'Never' }}
                    </p>
                </div>
                @empty
                <div class="md:col-span-2 lg:col-span-3 text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-robot text-3xl mb-2"></i>
                    <p>No agents registered. Agents will appear here once configured.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Cost Chart + Budget -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Daily Cost Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-chart-line mr-2 text-green-500"></i>AI Cost Spend (Daily)
                </h3>
                <div class="flex gap-2">
                    <button @click="toggleCostView('daily')" :class="costView === 'daily' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'" class="px-3 py-1 text-xs rounded-lg transition-colors">Daily</button>
                    <button @click="toggleCostView('monthly')" :class="costView === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'" class="px-3 py-1 text-xs rounded-lg transition-colors">Monthly</button>
                </div>
            </div>
            <div class="p-6">
                <canvas id="costChart" height="80"></canvas>
            </div>
        </div>

        <!-- Budget Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-wallet mr-2 text-yellow-500"></i>Budget
            </h3>
            @php
                $usedPercent = ($budgetLimit ?? 0) > 0 ? min((($budgetLimit - ($budgetRemaining ?? 0)) / $budgetLimit) * 100, 100) : 0;
                $budgetColor = $usedPercent > 80 ? 'bg-red-500' : ($usedPercent > 50 ? 'bg-yellow-500' : 'bg-green-500');
            @endphp
            <div class="text-center mb-4">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($monthlyTotal ?? 0, 4) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">This month's spend</p>
            </div>
            <div class="mb-3">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500 dark:text-gray-400">Budget Usage</span>
                    <span class="font-medium {{ $usedPercent > 80 ? 'text-red-600' : ($usedPercent > 50 ? 'text-yellow-600' : 'text-green-600') }}">{{ number_format($usedPercent, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="h-3 rounded-full {{ $budgetColor }} transition-all" style="width: {{ $usedPercent }}%"></div>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Budget Limit</span>
                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($budgetLimit ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Remaining</span>
                    <span class="font-medium {{ ($budgetRemaining ?? 0) < 1 ? 'text-red-600' : 'text-green-600' }}">
                        {{ ($budgetRemaining ?? 0) < 0 ? 'Unlimited' : '$' . number_format($budgetRemaining, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Progress + Cost By Agent -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Learning Progress -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-brain mr-2 text-purple-500"></i>Agent Learning Progress
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $learningStats['applied'] ?? 0 }}/{{ $learningStats['total'] ?? 0 }} improvements applied
                </p>
            </div>
            <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                @forelse($agentLearningProgress ?? [] as $agentName => $progress)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ ucwords(str_replace('_', ' ', $agentName)) }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $progress['applied'] }}/{{ $progress['total'] }} ({{ $progress['percent'] }}%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="h-3 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 transition-all"
                             style="width: {{ $progress['percent'] }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No learning data available yet.</p>
                @endforelse
            </div>
            <!-- Learning by Type -->
            @if(!empty($learningStats['by_type'] ?? []))
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">By Improvement Type</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($learningStats['by_type'] as $type => $count)
                    <span class="px-2.5 py-1 text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 rounded-full">
                        {{ ucwords(str_replace('_', ' ', $type)) }}: {{ $count }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Cost By Agent -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-coins mr-2 text-yellow-500"></i>Cost Breakdown by Agent
                </h3>
            </div>
            <div class="p-6">
                <canvas id="costByAgentChart" height="180"></canvas>
                <div class="mt-4 space-y-2">
                    @forelse($costByAgent ?? [] as $agentName => $costData)
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background: {{ ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'][$loop->index % 6] }}"></div>
                            <span class="text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $agentName)) }}</span>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-900 dark:text-white">${{ number_format($costData['total_cost_usd'] ?? 0, 4) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $costData['task_count'] ?? 0 }} tasks</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4 text-sm">No costs recorded this month.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Workflow Execution Log + Collaboration Graph -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Workflow Execution Log -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-list-check mr-2 text-blue-500"></i>Workflow Execution Log
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recent agent workflow executions</p>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto">
                <div class="space-y-3">
                    @forelse($executions ?? [] as $exec)
                    @php
                        $execStatusColors = [
                            'success' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            'running' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            'cancelled' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                        ];
                        $execColors = $execStatusColors[$exec->status] ?? $execStatusColors['pending'];
                    @endphp
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $execColors }}">
                                    {{ ucfirst($exec->status) }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ ucwords(str_replace('_', ' ', $exec->workflow_name)) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Steps: {{ $exec->steps_completed }}/{{ $exec->steps_total }}
                                @if($exec->duration_ms) &middot; {{ number_format($exec->duration_ms) }}ms @endif
                            </p>
                        </div>
                        <div class="text-right ml-4 shrink-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $exec->started_at ? \Carbon\Carbon::parse($exec->started_at)->diffForHumans() : 'N/A' }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8 text-sm">
                        <i class="fas fa-inbox text-2xl mb-2 block"></i>
                        No workflow executions recorded yet.
                    </p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Collaboration Graph -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-diagram-project mr-2 text-teal-500"></i>Agent Collaboration Graph
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cross-agent knowledge sharing</p>
            </div>
            <div class="p-6">
                <!-- Visual Graph (CSS-based) -->
                @if(!empty($collaborationEdges))
                <div class="relative mb-6">
                    <canvas id="collaborationChart" height="120"></canvas>
                </div>
                <div class="space-y-3">
                    @foreach($collaborationEdges as $edge)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ ucwords(str_replace('_', ' ', $edge['category'])) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ implode(', ', array_map(fn($a) => ucwords(str_replace('_', ' ', $a)), $edge['agents'])) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $edge['insight_count'] }} insights</p>
                            <p class="text-xs {{ $edge['avg_confidence'] >= 70 ? 'text-green-600' : ($edge['avg_confidence'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $edge['avg_confidence'] }}% confidence
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8 text-sm">
                    <i class="fas fa-diagram-project text-2xl mb-2 block"></i>
                    No cross-agent collaboration recorded yet.
                </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Learning Reports Table -->
    @if($learningReports ?? [])
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>Recent Learning Reports
            </h3>
        </div>
        <div class="p-6 max-h-72 overflow-y-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="pb-3 pr-4">Agent</th>
                        <th class="pb-3 pr-4">Type</th>
                        <th class="pb-3 pr-4">Description</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($learningReports as $report)
                    <tr class="text-sm">
                        <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $report->agent_name ?? 'Unknown')) }}</td>
                        <td class="py-2 pr-4">
                            <span class="px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 rounded-full">
                                {{ ucwords(str_replace('_', ' ', $report->improvement_type ?? 'general')) }}
                            </span>
                        </td>
                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $report->description ?? '—' }}</td>
                        <td class="py-2 pr-4">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ ($report->status === 'applied') ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                                {{ ucfirst($report->status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="py-2 text-gray-500 dark:text-gray-400">{{ $report->created_at ? \Carbon\Carbon::parse($report->created_at)->diffForHumans() : 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-4 text-center text-gray-500 dark:text-gray-400 text-sm">No learning reports.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function agentDashboard() {
    return {
        costView: 'daily',
        costChart: null,
        costByAgentChart: null,
        collaborationChart: null,
        lastUpdated: 'Just now',

        initCharts() {
            this.initCostChart();
            this.initCostByAgentChart();
            this.initCollaborationChart();
        },

        initCostChart() {
            const ctx = document.getElementById('costChart');
            if (!ctx) return;

            const rawData = @json($costTrendDaily ?? []);
            const labels = Object.keys(rawData).map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const costs = Object.values(rawData).map(v => v.cost_usd || 0);
            const tasks = Object.values(rawData).map(v => v.task_count || 0);

            this.costChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Cost (USD)',
                            data: costs,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Tasks',
                            data: tasks,
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            tension: 0.4,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.dataset.label + ': ' + (ctx.dataset.yAxisID === 'y' ? '$' + ctx.parsed.y.toFixed(6) : ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: { type: 'linear', position: 'left', title: { display: true, text: 'Cost (USD)' }, ticks: { callback: v => '$' + v.toFixed(4) } },
                        y1: { type: 'linear', position: 'right', title: { display: true, text: 'Tasks' }, grid: { drawOnChartArea: false } },
                    }
                }
            });
        },

        initCostByAgentChart() {
            const ctx = document.getElementById('costByAgentChart');
            if (!ctx) return;

            const rawData = @json($costByAgent ?? []);
            const labels = Object.keys(rawData).map(n => n.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
            const costs = Object.values(rawData).map(v => v.total_cost_usd || 0);
            const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'];

            this.costByAgentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: costs,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 12 } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                    return `${ctx.label}: $${ctx.parsed.toFixed(6)} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },

        initCollaborationChart() {
            const ctx = document.getElementById('collaborationChart');
            if (!ctx) return;

            const edges = @json($collaborationEdges ?? []);
            if (edges.length === 0) return;

            // Build a bar chart showing collaboration strength by category
            const labels = edges.map(e => e.category.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
            const insightCounts = edges.map(e => e.insight_count);
            const confidences = edges.map(e => e.avg_confidence);

            this.collaborationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Shared Insights',
                            data: insightCounts,
                            backgroundColor: '#14b8a6',
                            borderRadius: 4,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Avg Confidence %',
                            data: confidences,
                            backgroundColor: '#f59e0b',
                            borderRadius: 4,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Insights' } },
                        y1: { beginAtZero: true, max: 100, position: 'right', title: { display: true, text: 'Confidence %' }, grid: { drawOnChartArea: false } },
                    }
                }
            });
        },

        toggleCostView(view) {
            this.costView = view;
            // In a real app, this would fetch new data via AJAX
            // For now, we just update the label
        },

        refreshData() {
            window.location.reload();
        }
    }
}
</script>
@endpush
