@extends("layouts.unified")

@section('title', 'AI Training Jobs')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('ai-training.index') }}" class="hover:text-indigo-600">AI Training</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span>Jobs</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Training Jobs</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Track all training job progress and history.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Model</th>
                        <th>Version</th>
                        <th>Dataset</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Started</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td class="text-sm font-mono text-gray-600 dark:text-gray-400">#{{ $job->id }}</td>
                            <td>
                                <a href="{{ route('ai-training.show', $job->model_version_id) }}" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                    {{ $job->modelVersion->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">v{{ $job->modelVersion->version ?? '—' }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $job->dataset->name ?? 'N/A' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $job->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $job->status === 'running' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $job->status === 'queued' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                    {{ $job->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                    {{ $job->status === 'cancelled' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                ">{{ ucfirst($job->status) }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2 w-28">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="
                                            {{ $job->status === 'completed' ? 'bg-green-500' : '' }}
                                            {{ $job->status === 'running' ? 'bg-blue-500' : '' }}
                                            {{ $job->status === 'queued' ? 'bg-yellow-500' : '' }}
                                            {{ $job->status === 'failed' ? 'bg-red-500' : '' }}
                                            {{ $job->status === 'cancelled' ? 'bg-gray-500' : '' }}
                                            h-1.5 rounded-full
                                        " style="width: {{ $job->progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-8">{{ $job->progress }}%</span>
                                </div>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $job->started_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">
                                @if($job->started_at && $job->completed_at)
                                    {{ $job->started_at->diffForHumans($job->completed_at, true) }}
                                @elseif($job->started_at)
                                    {{ $job->started_at->diffForHumans() }} (ongoing)
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(in_array($job->status, ['queued', 'running']))
                                    <form method="POST" action="{{ route('ai-training.jobs.cancel', $job->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Cancel</button>
                                    </form>
                                @endif
                                @if($job->metrics)
                                    <span class="text-xs text-gray-500">Acc: {{ number_format(($job->metrics['accuracy'] ?? 0) * 100, 1) }}%</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-tasks text-4xl mb-3 block"></i>
                                No training jobs yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jobs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
@endsection
