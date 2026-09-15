@extends('layouts.unified')
@section('title', 'Agent Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Agents</li>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/agent-dashboard.css') }}">
@endsection

@section('content')
<div class="space-y-6">
<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('agents.dashboard') }}" class="btn btn-primary">
                <i class="fas fa-robot mr-1"></i> Agent Dashboard
            </a>
            <a href="{{ route('agents.workflows') }}" class="btn btn-info">
                <i class="fas fa-project-diagram mr-1"></i> Workflows
            </a>
            <button class="btn btn-success" onclick="runAudit()">
                <i class="fas fa-shield-alt mr-1"></i> Run Audit
            </button>
            <button class="btn btn-warning" onclick="runImprovement()">
                <i class="fas fa-magic mr-1"></i> Run Improvement
            </button>
            <a href="#costSummary" class="btn btn-secondary">
                <i class="fas fa-dollar-sign mr-1"></i> View Costs
            </a>
        </div>
    </div>
</div>

<!-- System Health Score -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat mr-2"></i>System Health</h3>
                <div class="card-tools">
                    @php
                        $overallStatus = $agentHealth['overall_status'] ?? 'healthy';
                    @endphp
                    <span class="badge badge-{{ $overallStatus === 'healthy' ? 'success' : ($overallStatus === 'degraded' ? 'warning' : 'danger') }} badge-lg">
                        {{ ucfirst($overallStatus) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-striped {{ ($agentHealth['system_score'] ?? 100) >= 95 ? 'bg-success' : (($agentHealth['system_score'] ?? 100) >= 80 ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $agentHealth['system_score'] ?? 100 }}%"
                                 aria-valuenow="{{ $agentHealth['system_score'] ?? 100 }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ $agentHealth['system_score'] ?? 100 }}%
                            </div>
                        </div>
                        <p class="mt-2 mb-0"><strong>Overall Health Score</strong></p>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="agent-stat-box agent-stat-success">
                            <h2 class="text-success mb-0">{{ $agentHealth['healthy_agents'] ?? 0 }}</h2>
                            <p class="text-muted">Healthy Agents</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="agent-stat-box agent-stat-warning">
                            <h2 class="text-warning mb-0">{{ $agentHealth['degraded_agents'] ?? 0 }}</h2>
                            <p class="text-muted">Degraded Agents</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="agent-stat-box agent-stat-info">
                            <h2 class="text-info mb-0">{{ $agentHealth['total_agents'] ?? 0 }}</h2>
                            <p class="text-muted">Total Agents</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agent Cards Row -->
<div class="row">
    @forelse($agents ?? [] as $name => $agent)
        @include('agents._agent-card', ['name' => $name, 'agent' => $agent])
    @empty
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i> No agents registered yet. Agents will appear here once they are registered with the orchestrator.
        </div>
    </div>
    @endforelse
</div>

<!-- Cost Summary Row -->
<div class="row" id="costSummary">
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Cost Summary (This Month)</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Total AI Cost:</span>
                    <span class="h4 text-primary">${{ number_format($costSummary['total'] ?? 0, 4) }}</span>
                </div>
                <hr>
                <h6>By Agent:</h6>
                <ul class="list-unstyled">
                    @forelse($costSummary['by_agent'] ?? [] as $agentName => $costData)
                    <li class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ ucwords(str_replace('_', ' ', $agentName)) }}</span>
                        <span class="badge badge-primary">${{ number_format($costData['total_cost_usd'] ?? 0, 4) }}</span>
                    </li>
                    @empty
                    <li class="text-muted">No costs recorded this month</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-wallet mr-2"></i>Budget Status</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Budget Limit:</span>
                    <span class="h4">${{ number_format($budgetLimit ?? 5.00, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Remaining:</span>
                    <span class="h4 text-{{ ($budgetRemaining ?? 5.00) < 1 ? 'danger' : 'success' }}">${{ number_format($budgetRemaining ?? 5.00, 2) }}</span>
                </div>
                <div class="progress progress-md">
                    @php
                        $usedPercent = ($budgetLimit ?? 5) > 0 ? min(((($budgetLimit ?? 5) - ($budgetRemaining ?? 0)) / ($budgetLimit ?? 5)) * 100, 100) : 0;
                    @endphp
                    <div class="progress-bar {{ $usedPercent > 80 ? 'bg-danger' : ($usedPercent > 50 ? 'bg-warning' : 'bg-success') }}" 
                         style="width: {{ $usedPercent }}%">
                        {{ number_format($usedPercent, 1) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Feed -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Agent Activity</h3>
                <div class="card-tools">
                    <span class="badge badge-info">Last 10 executions</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
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
                                <span class="badge badge-dark">{{ ucwords(str_replace('_', ' ', $activity->agent_name ?? 'Unknown')) }}</span>
                            </td>
                            <td>{{ str_replace('_', ' ', $activity->task_type ?? 'N/A') }}</td>
                            <td>
                                @if($activity->cost_usd > 0)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Success</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-minus mr-1"></i>Recorded</span>
                                @endif
                            </td>
                            <td>${{ number_format($activity->cost_usd ?? 0, 6) }}</td>
                            <td>{{ number_format($activity->tokens_used ?? 0) }}</td>
                            <td>{{ $activity->executed_at ? \Carbon\Carbon::parse($activity->executed_at)->diffForHumans() : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
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
</div>
@endsection

@push('scripts')
<script>
function runAudit() {
    if (confirm('Run a comprehensive security audit?')) {
        fetch('{{ route("agents.dispatch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                agent_name: 'security_agent',
                task_type: 'security_audit',
                prompt: 'Run comprehensive security audit for agency'
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success('Security audit dispatched successfully!');
            } else {
                toastr.error(data.message || 'Failed to dispatch audit.');
            }
        })
        .catch(() => toastr.error('Network error occurred.'));
    }
}

function runImprovement() {
    if (confirm('Run self-improvement analysis on all agents?')) {
        toastr.info('Self-improvement analysis initiated. Results will appear shortly.');
    }
}

function dispatchTask(agentName) {
    const taskType = prompt('Enter task type for ' + agentName + ':', 'content_generate');
    if (!taskType) return;
    
    const prompt = prompt('Enter task prompt:');
    if (!prompt) return;

    fetch('{{ route("agents.dispatch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            agent_name: agentName,
            task_type: taskType,
            prompt: prompt
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toastr.success('Task dispatched to ' + agentName + ' successfully!');
        } else {
            toastr.error(data.message || 'Failed to dispatch task.');
        }
    })
    .catch(() => toastr.error('Network error occurred.'));
}
</script>
@endpush
