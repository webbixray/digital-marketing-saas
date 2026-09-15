@extends('layouts.unified')
@section('title', $workflow->name)
@section('content')
<div class="space-y-6">
<div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Workflow Details</h3></div>
            <div class="card-body">
                <strong><i class="fas fa-bolt mr-1"></i> Trigger</strong><p class="text-muted">{{ \App\Models\Workflow::TRIGGER_TYPES[$workflow->trigger_type] ?? $workflow->trigger_type }}</p><hr>
                <strong><i class="fas fa-info-circle mr-1"></i> Status</strong><span class="badge badge-{{ $workflow->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($workflow->status) }}</span><hr>
                <strong><i class="fas fa-redo mr-1"></i> Executions</strong><p class="text-muted">{{ $workflow->execution_count }}</p><hr>
                <strong><i class="fas fa-clock mr-1"></i> Last Run</strong><p class="text-muted">{{ $workflow->last_executed_at?->diffForHumans() ?? 'Never' }}</p><hr>
                <strong><i class="fas fa-cogs mr-1"></i> Actions</strong><pre class="text-sm">{{ json_encode($workflow->actions, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Execution History</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Status</th><th>Started</th><th>Duration</th><th>Error</th></tr></thead>
                    <tbody>
                        @forelse($executions as $exec)
                            <tr>
                                <td><span class="badge badge-{{ $exec->status === 'success' ? 'success' : ($exec->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($exec->status) }}</span></td>
                                <td>{{ $exec->started_at?->diffForHumans() }}</td>
                                <td>{{ $exec->duration_ms }}ms</td>
                                <td><small class="text-danger">{{ $exec->error_message }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No executions yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
