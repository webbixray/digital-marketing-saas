@extends('layouts.unified')
@section('title', 'Agent Workflows')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('agents.dashboard') }}">Agents</a></li>
    <li class="breadcrumb-item active">Workflows</li>
@endsection

@section('content')
<div class="space-y-6">
<div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-project-diagram mr-2"></i>Workflow Templates</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ count($workflows ?? []) }} workflows</span>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Workflows chain multiple AI agents together to accomplish complex tasks automatically.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Workflow Templates Grid -->
<div class="row">
    @forelse($workflows ?? [] as $index => $workflow)
    <div class="col-lg-6 col-md-12 mb-3">
        <div class="card card-outline card-{{ $loop->even ? 'info' : 'success' }}">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-project-diagram mr-2"></i>{{ ucwords(str_replace('_', ' ', $workflow['name'])) }}
                </h3>
                <div class="card-tools">
                    <span class="badge badge-{{ count($workflow['required_features'] ?? []) <= 1 ? 'success' : 'warning' }}">
                        {{ count($workflow['required_features'] ?? []) }} features
                    </span>
                </div>
            </div>
            <div class="card-body">
                <p>{{ $workflow['description'] }}</p>
                
                <div class="mb-3">
                    <strong><i class="fas fa-cogs mr-1"></i>Steps:</strong>
                    <div class="mt-2">
                        @foreach($workflow['steps'] ?? [] as $stepIndex => $step)
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge badge-primary mr-2" style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">{{ $stepIndex + 1 }}</span>
                            <span>{{ str_replace('_', ' ', ucwords($step)) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-puzzle-piece mr-1"></i>Required Features:</strong>
                    <div class="mt-1">
                        @foreach($workflow['required_features'] ?? [] as $feature)
                            <span class="badge badge-light">{{ str_replace('_', ' ', ucwords($feature)) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-success btn-sm" onclick="runWorkflow('{{ $workflow['name'] }}')" id="btn-{{ $index }}">
                    <i class="fas fa-play mr-1"></i> Run Workflow
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="viewWorkflowDetails('{{ $workflow['name'] }}')">
                    <i class="fas fa-info-circle mr-1"></i> Details
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i> No workflow templates available.
        </div>
    </div>
    @endforelse
</div>

<!-- Execution History -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Workflow Execution History</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ count($executions ?? []) }} executions</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Execution ID</th>
                                <th>Workflow</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Started</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($executions ?? [] as $exec)
                            <tr>
                                <td><code>{{ Str::limit($exec->execution_id ?? 'N/A', 15) }}</code></td>
                                <td>
                                    <span class="badge badge-dark">{{ ucwords(str_replace('_', ' ', $exec->workflow_name ?? 'N/A')) }}</span>
                                </td>
                                <td>
                                    @php
                                        $status = $exec->status ?? 'unknown';
                                        $statusClass = match($status) {
                                            'success' => 'badge-success',
                                            'failed', 'cancelled' => 'badge-danger',
                                            'running' => 'badge-warning',
                                            default => 'badge-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                </td>
                                <td>
                                    @php
                                        $progress = ($exec->steps_total ?? 0) > 0 
                                            ? round(($exec->steps_completed ?? 0) / $exec->steps_total * 100) 
                                            : 0;
                                    @endphp
                                    <div class="progress progress-sm" style="width: 100px;">
                                        <div class="progress-bar bg-{{ $progress >= 100 ? 'success' : 'primary' }}" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <small>{{ $exec->steps_completed ?? 0 }}/{{ $exec->steps_total ?? 0 }}</small>
                                </td>
                                <td>{{ $exec->started_at ? \Carbon\Carbon::parse($exec->started_at)->format('M d, H:i') : 'N/A' }}</td>
                                <td>
                                    @if($exec->duration_ms)
                                        {{ number_format($exec->duration_ms / 1000, 1) }}s
                                    @elseif($exec->started_at)
                                        {{ \Carbon\Carbon::parse($exec->started_at)->diffForHumans() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($status === 'pending' || $status === 'running')
                                    <span class="text-warning"><i class="fas fa-spinner fa-spin mr-1"></i> Running...</span>
                                    @elseif($status === 'success')
                                    <span class="text-success"><i class="fas fa-check mr-1"></i> Complete</span>
                                    @else
                                    <span class="text-danger"><i class="fas fa-times mr-1"></i> Failed</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No workflow executions recorded yet. Run a workflow to see it here.</p>
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

<!-- Workflow Execution Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i>Workflow Result</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="resultContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Processing workflow...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    async function runWorkflow(workflowName) {
        if (!confirm('Run the "' + workflowName.replace(/_/g, ' ') + '" workflow?')) return;
        
        const btn = document.querySelector('button[onclick*="' + workflowName + '"]');
        dmsaas.setLoading(btn, true);
        
        try {
            const response = await dmsaas.request('/agents/run-workflow', {
                method: 'POST',
                body: JSON.stringify({
                    workflow_name: workflowName,
                    async: true,
                })
            });
            const data = await response.json();
            if (data.success) {
                dmsaas.toast('Workflow started successfully!');
                setTimeout(() => location.reload(), 2000);
            } else {
                dmsaas.toast(data.message || 'Failed to start workflow.', 'error');
            }
        } catch (err) {
            dmsaas.toast('Network error. Please try again.', 'error');
        } finally {
            dmsaas.setLoading(btn, false);
        }
    }

    function viewWorkflowDetails(workflowName) {
        const workflows = @json($workflows ?? []);
        const workflow = workflows.find(w => w.name === workflowName);
        
        if (workflow) {
            let stepsHtml = workflow.steps.map((step, i) => 
                `<div class="d-flex align-items-center mb-2">
                    <span class="badge badge-primary mr-2">${i + 1}</span>
                    <span>${step.replace(/_/g, ' ')}</span>
                </div>`
            ).join('');
            
            document.getElementById('resultContent').innerHTML = `
                <h5>${workflow.name.replace(/_/g, ' ')}</h5>
                <p class="text-muted">${workflow.description}</p>
                <hr>
                <h6>Workflow Steps:</h6>
                ${stepsHtml}
                <hr>
                <h6>Required Features:</h6>
                <div>${workflow.required_features.map(f => `<span class="badge badge-info mr-1">${f.replace(/_/g, ' ')}</span>`).join('')}</div>
            `;
            document.getElementById('resultModal').modal('show');
        }
    }
</script>
@endpush
