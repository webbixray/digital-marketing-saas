@extends('layouts.unified')
@section('title', 'Reports')

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Generated Reports</h3>
                        <div>
                            <a href="{{ route('reports.index') }}" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 text-gray-700 dark:text-gray-300 font-medium transition-colors text-sm">All</a>
                        </div>
                    </div>
                </div>
                <div class="p-0">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Format</th><th>Schedule</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->name }}</td>
                                <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $report->type }}</span></td>
                                <td>{{ strtoupper($report->format) }}</td>
                                <td>{{ ucfirst($report->schedule) }}</td>
                                <td><span class="px-2 py-1 text-xs font-medium rounded-full { $report->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }">{{ ucfirst($report->status) }}</span></td>
                                <td>
                                    @if($report->file_path)<a href="{{ route('reports.download', $report) }}" class="px-3 py-1 border border-indigo-600 dark:border-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-medium transition-colors text-sm"><i class="fas fa-download"></i></a>@endif
                                    <form action="{{ route('reports.destroy', $report) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this report?')">@csrf @method('DELETE')<button class="px-3 py-1 border border-red-600 dark:border-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 font-medium transition-colors text-sm"><i class="fas fa-trash"></i></button></form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">No reports found</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $reports->links() }}</div>
            </div>
</div>
@endsection

