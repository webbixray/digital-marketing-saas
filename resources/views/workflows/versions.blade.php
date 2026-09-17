@extends('layouts.unified')
@section('title', 'Workflow Versions')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Version History: {{ $workflow->name }}</h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('workflows.show', $workflow) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors text-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        </div>
    </div>
    <div class="p-0">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead><tr><th>Version</th><th>Name</th><th>Trigger</th><th>Changes</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($versions as $version)
                    <tr>
                        <td><strong>v{{ $version->version_number }}</strong></td>
                        <td>{{ $version->name }}</td>
                        <td>{{ \App\Models\Workflow::TRIGGER_TYPES[$version->trigger_type] ?? $version->trigger_type }}</td>
                        <td>{{ $version->change_notes ?? '—' }}</td>
                        <td>{{ $version->created_at->diffForHumans() }}</td>
                        <td>
                            <form action="{{ route('workflows.versions.restore', [$workflow, $version]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-yellow-500 text-white px-2 py-0.5 rounded hover:bg-yellow-600 inline-flex items-center gap-1 font-medium transition-colors text-xs" onclick="return confirm('Restore to version {{ $version->version_number }}? Current state will be saved as a new version.')">
                                    <i class="fas fa-undo mr-1"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 dark:text-gray-400">No versions found</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $versions->links() }}</div>
</div>
</div>
@endsection
