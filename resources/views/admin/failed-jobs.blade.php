@extends("layouts.unified")

@section('title', 'Failed Jobs')

@section('content')
<x-flash-messages />
<div class="mb-8 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Failed Jobs</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View and retry failed background jobs.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Connection</th>
                <th scope="col">Queue</th>
                <th scope="col">Failed At</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($failedJobs as $job)
                <tr>
                    <td>{{ $job->id }}</td>
                    <td>{{ $job->connection_name }}</td>
                    <td>{{ $job->queue }}</td>
                    <td>{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</td>
                    <td>
                        <form method="POST" action="{{ route('failed-jobs.retry', $job->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-1 text-sm font-medium transition-colors text-gray-700 dark:text-gray-200">
                                <i class="fas fa-redo"></i> Retry
                            </button>
                        </form>
                        <form method="POST" action="{{ route('failed-jobs.delete', $job->id) }}" class="inline ml-2">
                            @csrf @method('DELETE')
                            <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 inline-flex items-center gap-1 text-sm font-medium transition-colors"
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $job->id }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <x-confirm-modal id="confirm-delete-{{ $job->id }}" title="Delete Failed Job"
                            message="Are you sure you want to delete this failed job?"
                            confirm-text="Delete" cancel-text="Cancel"
                            x-on:confirm="event.target.closest('form').submit()" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">No failed jobs</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($failedJobs->hasPages())
    <div class="p-4">
        {{ $failedJobs->links() }}
    </div>
    @endif
</div>
@endsection
