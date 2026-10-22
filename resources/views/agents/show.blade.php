@extends('layouts.unified')
@section('title', $agent['name'] ?? 'Agent Details')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('agents.dashboard') }}">Agents</a></li>
 <li class="text-gray-900 font-medium">{{ ucwords(str_replace('_', ' ', $agentName)) }}</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Details</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View and manage agent performance.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Agent Info Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-robot mr-2"></i>{{ ucwords(str_replace('_', ' ', $agent['name'] ?? $agentName)) }}</h3>
            </div>
            <div class="p-6">
                <div class="text-center mb-3">
                    <div class="text-indigo-600 dark:text-indigo-400 mb-2">
                        <i class="fas fa-robot text-3xl"></i>
                    </div>
                    <h4>{{ ucwords(str_replace('_', ' ', $agent['name'] ?? $agentName)) }}</h4>
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ ($agent['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : (($agent['status'] ?? 'active') === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">
                        {{ ucfirst($agent['status'] ?? 'Active') }}
                    </span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700 my-3">
                <p><strong><i class="fas fa-info-circle mr-2"></i>Description:</strong></p>
                <p class="text-gray-500 dark:text-gray-400">{{ $agent['description'] ?? 'An intelligent AI agent handling specialized tasks.' }}</p>
                
                <p><strong><i class="fas fa-tags mr-2"></i>Category:</strong></p>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($agent['category'] ?? 'General') }}</span>
                
                <p class="mt-3"><strong><i class="fas fa-tasks mr-2"></i>Supported Task Types:</strong></p>
                <div>
                    @forelse($agent['supported_types'] ?? [] as $type)
                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-600 dark:text-gray-300 mb-1">{{ str_replace('_', ' ', $type) }}</span>
                    @empty
                    <span class="text-gray-500 dark:text-gray-400">No task types defined</span>
                    @endforelse
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('agents.dashboard') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors text-gray-700 dark:text-gray-200 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Agents
                </a>
            </div>
        </div>

        <!-- Performance Metrics & History -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Learned Patterns Card -->
            <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-brain mr-2"></i>Learned Patterns</h3>
                </div>
                <div class="p-6">
                    @if(!empty($learnedPatterns))
                    <ul class="space-y-2">
                        @foreach($learnedPatterns as $pattern)
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-yellow-600 dark:text-yellow-400 mr-2"></i>{{ $pattern }}
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-3">
                        <i class="fas fa-brain text-2xl mb-2"></i>
                        <p>No learned patterns yet. The agent will learn from executions over time.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format(($agent['success_rate'] ?? 0) * 100, 1) }}%</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Success Rate</p>
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($agent['total_executed'] ?? 0) }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Executions</p>
                    </div>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-6">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($agent['avg_cost_per_task'] ?? 0, 4) }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Avg Cost/Task</p>
                    </div>
                </div>
            </div>

            <!-- Additional Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-tachometer-alt mr-2"></i>Performance Scores</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <span>Speed Score</span>
                                <span><b>{{ number_format(($agent['speed_score'] ?? 0.5) * 100) }}%</b></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ ($agent['speed_score'] ?? 0.5) * 100 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <span>Cost Efficiency</span>
                                <span><b>{{ number_format(($agent['cost_score'] ?? 0.5) * 100) }}%</b></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <div class="bg-green-600 h-1.5 rounded-full" style="width: {{ ($agent['cost_score'] ?? 0.5) * 100 }}%"></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Total Cost</span>
                            <span><b>${{ number_format($agent['total_cost'] ?? 0, 4) }}</b></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Total Successes</span>
                            <span><b>{{ number_format($agent['total_successes'] ?? 0) }}</b></span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-paper-plane mr-2"></i>Dispatch Task</h3>
                    </div>
                    <div class="p-6">
                        <form id="dispatchForm" action="{{ route('agents.dispatch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="agent_name" value="{{ $agentName }}">
                            <div class="mb-4">
                                <label for="task_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Type</label>
                                <select name="task_type" id="task_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="">Select a task type...</option>
                                    @foreach($agent['supported_types'] ?? [] as $type)
                                    <option value="{{ $type }}">{{ str_replace('_', ' ', ucwords($type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Prompt</label>
                                <textarea name="prompt" id="prompt" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="4" 
                                    placeholder="Describe what you want the agent to do..."
                                    required maxlength="10000"></textarea>
                            </div>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center justify-center gap-2 font-medium transition-colors w-full">
                                <i class="fas fa-paper-plane mr-1"></i> Dispatch Task
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Execution History -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Recent Execution History</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ count($executions ?? []) }} records</span>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cost</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tokens</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($executions ?? [] as $exec)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $exec->executed_at ? \Carbon\Carbon::parse($exec->executed_at)->format('M d, Y H:i') : 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', $exec->task_type ?? 'N/A') }}</span></td>
                                    <td class="px-4 py-3">
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"><i class="fas fa-check mr-1"></i>Recorded</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${{ number_format($exec->cost_usd ?? 0, 6) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ number_format($exec->tokens_used ?? 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">-</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-inbox text-2xl mb-2"></i>
                                        <p>No execution history recorded for this agent.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
 document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dispatchForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = form.querySelector('button[type=submit]');
            dmsaas.setLoading(btn, true);
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form)
                });
                const data = await response.json();
                if (data.success) {
                    dmsaas.toast('Task dispatched successfully!');
                    location.reload();
                } else {
                    dmsaas.toast(data.message || 'Failed to dispatch task.', 'error');
                }
            } catch (err) {
                dmsaas.toast('Network error. Please try again.', 'error');
            } finally {
                dmsaas.setLoading(btn, false);
            }
        });
    }
 });
</script>
@endpush
