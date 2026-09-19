@extends('layouts.unified')
@section('title', 'Backup Management')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Backups</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage and restore system backups.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">System Backups</h3>
            <form action="{{ route('system.backup.store') }}" method="POST">
                @csrf
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
                    <i class="fas fa-plus"></i> Create Backup
                </button>
            </form>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Filename</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($backups as $backup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">{{ $backup['filename'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $backup['size'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $backup['date'] }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('system.backup.restore', $backup['filename']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-yellow-500 text-white px-3 py-1 text-sm rounded-lg hover:bg-yellow-600 inline-flex items-center gap-1 font-medium transition-colors"
                                                onclick="return confirm('Are you sure you want to restore this backup?')">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">No backups found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
 @endsection
