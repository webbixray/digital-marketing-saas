@php
    $status = $agent['status'] ?? 'active';
    $statusClass = $status === 'active' ? 'card-success' : ($status === 'warning' ? 'card-warning' : 'card-danger');
    $badgeClass = $status === 'active' ? 'badge-success' : ($status === 'warning' ? 'badge-warning' : 'badge-danger');
    $statusIcon = $status === 'active' ? 'fa-check-circle' : ($status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
@endphp

<div class="col-lg-4 col-md-6 mb-3">
    <div class="card {{ $statusClass }} card-outline agent-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-robot mr-2"></i>{{ ucwords(str_replace('_', ' ', $name)) }}
            </h3>
            <div class="card-tools">
                <span class="badge {{ $badgeClass }} agent-status-badge" data-status="{{ $status }}">
                    <i class="fas {{ $statusIcon }} mr-1"></i>{{ ucfirst($status) }}
                </span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-12 gap-4>
                <div class="col-6">
                    <div class="description-block border-right">
                        <h5 class="description-header text-{{ ($agent['success_rate'] ?? 0) >= 0.8 ? 'success' : (($agent['success_rate'] ?? 0) >= 0.5 ? 'warning' : 'danger') }}">
                            {{ number_format(($agent['success_rate'] ?? 0) * 100, 1) }}%
                        </h5>
                        <span class="description-text">Success Rate</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="description-block">
                        <h5 class="description-header">{{ number_format($agent['total_executed'] ?? 0) }}</h5>
                        <span class="description-text">Total Executions</span>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6">
                    <div class="description-block border-right">
                        <h5 class="description-header text-info">{{ number_format($agent['total_successes'] ?? 0) }}</h5>
                        <span class="description-text">Successful</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="description-block">
                        <h5 class="description-header text-primary">${{ number_format($agent['avg_cost_per_task'] ?? 0, 4) }}</h5>
                        <span class="description-text">Avg Cost/Task</span>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-clock mr-1"></i>
                    Last run: {{ $agent['last_run'] ?? 'Never' }}
                </small>
                <br>
                <small class="text-muted">
                    <i class="fas fa-dollar-sign mr-1"></i>
                    Total cost: ${{ number_format($agent['total_cost'] ?? 0, 4) }}
                </small>
                <br>
                <small class="text-muted">
                    <i class="fas fa-layer-group mr-1"></i>
                    Category: <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $agent['category'] ?? 'General' }}</span>
                </small>
            </div>
            @if(!empty($agent['description']))
            <div class="mt-3">
                <p class="text-muted small mb-0">{{ $agent['description'] }}</p>
            </div>
            @endif
            <div class="mt-3">
                <span class="text-muted small">Supported Tasks:</span>
                <div class="mt-1">
                    @foreach(array_slice($agent['supported_types'] ?? [], 0, 4) as $type)
                        <span class="badge badge-light">{{ str_replace('_', ' ', $type) }}</span>
                    @endforeach
                    @if(count($agent['supported_types'] ?? []) > 4)
                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">+{{ count($agent['supported_types']) - 4 }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('agents.show', $name) }}" class="btn btn-sm btn-primary">
                <i class="fas fa-eye mr-1"></i> View Details
            </a>
            <button class="btn btn-sm btn-success" onclick="dispatchTask('{{ $name }}')">
                <i class="fas fa-paper-plane mr-1"></i> Dispatch
            </button>
        </div>
    </div>
</div>
