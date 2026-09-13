@extends('layouts.modern')

@section('title', 'Workflows')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Workflows</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Automate your marketing tasks.</p>
        </div>
        <a href="{{ route('workflows.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Workflow
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Trigger</th>
                        <th>Status</th>
                        <th>Executions</th>
                        <th>Last Run</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workflows as $workflow)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                        <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $workflow->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $workflow->description ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="capitalize text-gray-500 dark:text-gray-400">{{ $workflow->trigger_type ?? 'manual' }}</td>
                            <td>
                                <span class="badge {{ ($workflow->status ?? 'draft') === 'active' ? 'badge-success' : 'badge-warning' }}">
                                    {{ ucfirst($workflow->status ?? 'draft') }}
                                </span>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $workflow->execution_count ?? 0 }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $workflow->last_executed_at ? $workflow->last_executed_at->diffForHumans() : 'Never' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('workflows.edit', $workflow) }}" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('workflows.destroy', $workflow) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-project-diagram text-4xl mb-4 block"></i>
                                No workflows found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
