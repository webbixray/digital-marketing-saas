@extends('layouts.unified')
@section('title', 'Workflow Versions')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Version History: {{ $workflow->name }}</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View and restore previous versions of this workflow.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Version History</h3>
            <a href="{{ route('workflows.show', $workflow) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors text-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Version</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trigger</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Changes</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($versions as $version)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><strong>v{{ $version->version_number }}</strong></td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $version->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ \App\Models\Workflow::TRIGGER_TYPES[$version->trigger_type] ?? $version->trigger_type }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $version->change_notes ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $version->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <form action="{{ route('workflows.versions.restore', [$workflow, $version]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-yellow-500 text-white px-2 py-0.5 rounded hover:bg-yellow-600 inline-flex items-center gap-1 font-medium transition-colors text-xs" onclick="return confirm('Restore to version {{ $version->version_number }}? Current state will be saved as a new version.')">
                                        <i class="fas fa-undo mr-1"></i> Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No versions found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $versions->links() }}</div>
    </div>
</div>
 @endsection
