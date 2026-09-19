@extends('layouts.unified')
@section('title', 'Reports')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">Generated Reports</h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.index') }}" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 text-gray-700 dark:text-gray-300 font-medium transition-colors text-sm">All</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Format</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Schedule</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $report->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $report->type }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ strtoupper($report->format) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($report->schedule) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $report->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($report->file_path)
                                <a href="{{ route('reports.download', $report) }}" class="px-3 py-1 border border-indigo-600 dark:border-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-medium transition-colors text-sm" title="Download"><i class="fas fa-download"></i></a>
                                @endif
                                <form action="{{ route('reports.destroy', $report) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this report?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1 border border-red-600 dark:border-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 font-medium transition-colors text-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-bar text-5xl mb-4 block text-gray-300 dark:text-gray-600"></i>
                            <p class="text-sm font-medium">No reports found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $reports->links() }}</div>
        @endif
    </div>
</div>
