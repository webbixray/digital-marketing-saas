@extends('layouts.unified')
@section('title', $workflow->name)

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Workflow Details</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $workflow->name }} - View configuration and execution history.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Workflow Details</h3>
                </div>
                <div class="p-6">
                    <strong><i class="fas fa-bolt mr-1"></i> Trigger</strong>
                    <p class="text-gray-500 dark:text-gray-400">{{ \App\Models\Workflow::TRIGGER_TYPES[$workflow->trigger_type] ?? $workflow->trigger_type }}</p>
                    <hr class="border-gray-200 dark:border-gray-700 my-3">
                    
                    <strong><i class="fas fa-info-circle mr-1"></i> Status</strong>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $workflow->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($workflow->status) }}</span>
                    <hr class="border-gray-200 dark:border-gray-700 my-3">
                    
                    <strong><i class="fas fa-redo mr-1"></i> Executions</strong>
                    <p class="text-gray-500 dark:text-gray-400">{{ $workflow->execution_count }}</p>
                    <hr class="border-gray-200 dark:border-gray-700 my-3">
                    
                    <strong><i class="fas fa-clock mr-1"></i> Last Run</strong>
                    <p class="text-gray-500 dark:text-gray-400">{{ $workflow->last_executed_at?->diffForHumans() ?? 'Never' }}</p>
                    <hr class="border-gray-200 dark:border-gray-700 my-3">
                    
                    <strong><i class="fas fa-cogs mr-1"></i> Actions</strong>
                    <pre class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ json_encode($workflow->actions, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Execution History</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($executions as $exec)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $exec->status === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($exec->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') }}">{{ ucfirst($exec->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $exec->started_at?->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $exec->duration_ms }}ms</td>
                                    <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ $exec->error_message }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No executions yet</td></tr>
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
