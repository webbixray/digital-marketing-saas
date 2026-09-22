@extends('layouts.unified')

@section('title', 'Compliance Audit Log')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Compliance Audit Log</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Search and filter all GDPR/CCPA compliance actions.</p>
        </div>
        <a href="{{ route('admin.gdpr.dashboard') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 mb-8">
    <form method="GET" action="{{ route('admin.gdpr.audit-log') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action</label>
            <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">All Actions</option>
                @foreach($actions as $action => $count)
                    <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>
                        {{ $action }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat => $count)
                    <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>
                        {{ strtoupper($cat) }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Severity</label>
            <select name="severity" class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">All Severities</option>
                @foreach($severities as $sev => $count)
                    <option value="{{ $sev }}" {{ ($filters['severity'] ?? '') === $sev ? 'selected' : '' }}>
                        {{ ucfirst($sev) }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
        </div>

        <div class="md:col-span-5 flex items-center justify-end gap-3">
            <a href="{{ route('admin.gdpr.audit-log') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                Clear
            </a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </div>
    </form>
</div>

<!-- Audit Log Table -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900 dark:text-white">Audit Entries ({{ $audits->total() }})</h3>
    </div>
    <div class="p-6">
        @if($audits->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No audit entries match your filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Severity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IP</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($audits as $audit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2 text-sm text-gray-400">{{ $audit->id }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">{{ $audit->action }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $audit->category === 'ccpa' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                        {{ strtoupper($audit->category) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $audit->user?->name ?? 'System' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                        @if($audit->severity === 'critical') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                        @elseif($audit->severity === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ ucfirst($audit->severity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $audit->ip_address }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $audit->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 font-mono max-w-[200px] truncate">
                                    @if($audit->metadata)
                                        <span title="{{ json_encode($audit->metadata) }}">{{ json_encode($audit->metadata) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $audits->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
