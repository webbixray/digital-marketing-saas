@extends('layouts.unified')
@section('title', $workflow->name)
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Workflow Details</h3></div>
            <div class="p-6">
                <strong><i class="fas fa-bolt mr-1"></i> Trigger</strong><p class="text-gray-500 dark:text-gray-400">{{ \App\Models\Workflow::TRIGGER_TYPES[$workflow->trigger_type] ?? $workflow->trigger_type }}</p><hr>
                <strong><i class="fas fa-info-circle mr-1"></i> Status</strong><span class="px-2 py-1 text-xs font-medium rounded-full { $workflow->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }">{{ ucfirst($workflow->status) }}</span><hr>
                <strong><i class="fas fa-redo mr-1"></i> Executions</strong><p class="text-gray-500 dark:text-gray-400">{{ $workflow->execution_count }}</p><hr>
                <strong><i class="fas fa-clock mr-1"></i> Last Run</strong><p class="text-gray-500 dark:text-gray-400">{{ $workflow->last_executed_at?->diffForHumans() ?? 'Never' }}</p><hr>
                <strong><i class="fas fa-cogs mr-1"></i> Actions</strong><pre class="text-sm">{{ json_encode($workflow->actions, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Execution History</h3></div>
            <div class="p-0">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead><tr><th>Status</th><th>Started</th><th>Duration</th><th>Error</th></tr></thead>
                    <tbody>
                        @forelse($executions as $exec)
                            <tr>
                                <td><span class="px-2 py-1 text-xs font-medium rounded-full { $exec->status === 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($exec->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') }">{{ ucfirst($exec->status) }}</span></td>
                                <td>{{ $exec->started_at?->diffForHumans() }}</td>
                                <td>{{ $exec->duration_ms }}ms</td>
                                <td><small class="text-red-600 dark:text-red-400">{{ $exec->error_message }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-500 dark:text-gray-400">No executions yet</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
