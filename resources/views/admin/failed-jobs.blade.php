@extends("layouts.unified")

@section('title', 'Failed Jobs')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Failed Jobs</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">View and retry failed background jobs.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="table-responsive">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Connection</th>
                        <th>Queue</th>
                        <th>Failed At</th>
                        <th>Actions</th>
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
                                    <button type="submit" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('failed-jobs.delete', $job->id) }}" class="inline ml-2">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">No failed jobs</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        @if($failedJobs->hasPages())
            <div class="p-4">
                {{ $failedJobs->links() }}
            </div>
        @endif
    </div>
@endsection
