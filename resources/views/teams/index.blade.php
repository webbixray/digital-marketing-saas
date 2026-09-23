@extends('layouts.unified')

@section('title', 'Teams')

@section('content')
<div x-data="teamsIndex()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Teams</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your agency teams and members</p>
        </div>
        <a href="{{ route('teams.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-plus"></i>
            <span>Add Team</span>
        </a>
    </div>

    <!-- Teams Grid -->
    @if($teams->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No teams yet</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Create your first team to start collaborating with your agency members.</p>
            <a href="{{ route('teams.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-plus"></i>
                Create Team
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($teams as $team)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Card Header -->
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">{{ $team->name }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $team->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $team->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if($team->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">{{ $team->description }}</p>
                        @endif
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-users text-xs"></i>
                                {{ $team->users_count }} members
                            </span>
                            <span class="flex items-center gap-1">
                                <i class="fas fa-user-shield text-xs"></i>
                                {{ $team->owner?->name ?? 'Unknown' }}
                            </span>
                        </div>
                    </div>
                    <!-- Card Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <a href="{{ route('teams.show', $team) }}"
                           class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                            View Details
                        </a>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('teams.edit', $team) }}"
                               class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <button @click="confirmDelete({{ $team->id }})"
                                    class="p-2 text-red-400 hover:text-red-600 dark:hover:text-red-300" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $teams->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="showDeleteModal = false"></div>
            <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Delete Team</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to delete this team? This action cannot be undone.</p>
                <div class="flex items-center justify-end gap-3">
                    <button @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <form :action="deleteAction" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function teamsIndex() {
    return {
        showDeleteModal: false,
        deleteAction: '',
        teamToDelete: null,
        confirmDelete(teamId) {
            this.teamToDelete = teamId;
            this.deleteAction = `/teams/${teamId}`;
            this.showDeleteModal = true;
        }
    }
}
</script>
@endpush
