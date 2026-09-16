@extends('layouts.unified')
@section('title', $agent['name'] ?? 'Agent Details')
@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('agents.dashboard') }}">Agents</a></li>
    <li class="text-gray-900 font-medium">{{ ucwords(str_replace('_', ' ', $agentName)) }}</li>
@endsection

@section('content')
<div class="space-y-6">
<!-- Agent Info Card -->
    <div class="col-span-12 md:col-span-4">
        <div class="card card-primary card-outline">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-robot mr-2"></i>{{ ucwords(str_replace('_', ' ', $agent['name'] ?? $agentName)) }}</h3>
            </div>
            <div class="p-6">
                <div class="text-center mb-3">
                    <div class="fa-3x text-primary mb-2">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h4>{{ ucwords(str_replace('_', ' ', $agent['name'] ?? $agentName)) }}</h4>
                    <span class="badge badge-{{ ($agent['status'] ?? 'active') === 'active' ? 'success' : (($agent['status'] ?? 'active') === 'warning' ? 'warning' : 'danger') }}">
                        {{ ucfirst($agent['status'] ?? 'Active') }}
                    </span>
                </div>
                <hr>
                <p><strong><i class="fas fa-info-circle mr-2"></i>Description:</strong></p>
                <p class="text-muted">{{ $agent['description'] ?? 'An intelligent AI agent handling specialized tasks.' }}</p>
                
                <p><strong><i class="fas fa-tags mr-2"></i>Category:</strong></p>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($agent['category'] ?? 'General') }}</span>
                
                <p class="mt-3"><strong><i class="fas fa-tasks mr-2"></i>Supported Task Types:</strong></p>
                <div>
                    @forelse($agent['supported_types'] ?? [] as $type)
                        <span class="badge badge-light mb-1">{{ str_replace('_', ' ', $type) }}</span>
                    @empty
                        <span class="text-muted">No task types defined</span>
                    @endforelse
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('agents.dashboard') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Agents
                </a>
            </div>
        </div>

        <!-- Learned Patterns Card -->
        <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-brain mr-2"></i>Learned Patterns</h3>
            </div>
            <div class="p-6">
                @if(!empty($learnedPatterns))
                    <ul class="list-unstyled">
                        @foreach($learnedPatterns as $pattern)
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-warning mr-2"></i>{{ $pattern }}
                        </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-brain fa-2x mb-2"></i>
                        <p>No learned patterns yet. The agent will learn from executions over time.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance Metrics & History -->
    <div class="col-span-12 md:col-span-8">
        <!-- Performance Metrics -->
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12 md:col-span-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format(($agent['success_rate'] ?? 0) * 100, 1) }}%</h3>
                        <p>Success Rate</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($agent['total_executed'] ?? 0) }}</h3>
                        <p>Total Executions</p>
                    </div>
                    <div class="icon"><i class="fas fa-play-circle"></i></div>
                </div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>${{ number_format($agent['avg_cost_per_task'] ?? 0, 4) }}</h3>
                        <p>Avg Cost/Task</p>
                    </div>
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12 md:col-span-6">
                <div class="card card-outline card-info">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-tachometer-alt mr-2"></i>Performance Scores</h3>
                    </div>
                    <div class="p-6">
                        <div class="progress-group">
                            <span class="progress-text">Speed Score</span>
                            <span class="float-right"><b>{{ number_format(($agent['speed_score'] ?? 0.5) * 100) }}%</b></span>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($agent['speed_score'] ?? 0.5) * 100 }}%"></div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">Cost Efficiency</span>
                            <span class="float-right"><b>{{ number_format(($agent['cost_score'] ?? 0.5) * 100) }}%</b></span>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ ($agent['cost_score'] ?? 0.5) * 100 }}%"></div>
                            </div>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">Total Cost</span>
                            <span class="float-right"><b>${{ number_format($agent['total_cost'] ?? 0, 4) }}</b></span>
                        </div>
                        <div class="progress-group">
                            <span class="progress-text">Total Successes</span>
                            <span class="float-right"><b>{{ number_format($agent['total_successes'] ?? 0) }}</b></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 md:col-span-6">
                <div class="card card-outline card-success">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-paper-plane mr-2"></i>Dispatch Task</h3>
                    </div>
                    <div class="p-6">
                        <form id="dispatchForm" action="{{ route('agents.dispatch') }}" method="POST">
                            @csrf
                            <input type="hidden" name="agent_name" value="{{ $agentName }}">
                            <div class="mb-4">
                                <label for="task_type">Task Type</label>
                                <select name="task_type" id="task_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="">Select a task type...</option>
                                    @foreach($agent['supported_types'] ?? [] as $type)
                                        <option value="{{ $type }}">{{ str_replace('_', ' ', ucwords($type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="prompt">Task Prompt</label>
                                <textarea name="prompt" id="prompt" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="4" 
                                          placeholder="Describe what you want the agent to do..."
                                          required maxlength="10000"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane mr-1"></i> Dispatch Task
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Execution History -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Recent Execution History</h3>
                <div class="card-tools">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ count($executions ?? []) }} records</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Task Type</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Tokens</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($executions ?? [] as $exec)
                            <tr>
                                <td>{{ $exec->executed_at ? \Carbon\Carbon::parse($exec->executed_at)->format('M d, Y H:i') : 'N/A' }}</td>
                                <td><span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', $exec->task_type ?? 'N/A') }}</span></td>
                                <td>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"><i class="fas fa-check mr-1"></i>Recorded</span>
                                </td>
                                <td>${{ number_format($exec->cost_usd ?? 0, 6) }}</td>
                                <td>{{ number_format($exec->tokens_used ?? 0) }}</td>
                                <td>-</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No execution history recorded for this agent.</p>
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
</div>
@endsection

@push('scripts')
<script>
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
