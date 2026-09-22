@extends('layouts.unified')

@section('title', 'GDPR/CCPA Compliance Dashboard')

@section('content')
<x-flash-messages />

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">GDPR/CCPA Compliance Dashboard</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor privacy compliance, manage data requests, and audit consent records.</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('admin.gdpr.retention-cleanup') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-broom mr-2"></i>Run Retention Cleanup
                </button>
            </form>
            <form method="POST" action="{{ route('admin.gdpr.consent-expiry') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-clock mr-2"></i>Run Consent Expiry
                </button>
            </form>
            <a href="{{ route('admin.gdpr.audit-log') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-list-alt mr-2"></i>View Audit Log
            </a>
        </div>
    </div>
</div>

<!-- Compliance Overview Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Audits</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $overview['total_audits'] }}</p>
            </div>
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-clipboard-check text-indigo-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Exports</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $overview['pending_exports'] }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-export text-yellow-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Deletions</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $overview['pending_deletions'] }}</p>
            </div>
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-trash-alt text-red-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Consents</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $overview['active_consents'] }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CCPA Opt-outs</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $overview['ccpa_opt_outs'] }}</p>
            </div>
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Expired Consents / Critical Alerts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expired Consents</p>
            <span class="text-lg font-bold text-orange-600">{{ $overview['expired_consents'] }}</span>
        </div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Withdrawn Consents</p>
            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $overview['withdrawn_consents'] }}</span>
        </div>
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Critical Events</p>
            <span class="text-lg font-bold text-red-600">{{ $overview['critical_events'] }}</span>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Consents by Type</p>
        <div class="grid grid-cols-3 gap-4">
            @foreach($consentStats['by_type'] as $type => $count)
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ ucfirst($type) }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $count }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Consent Trend Chart -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 mb-8">
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Consent Activity (Last 30 Days)</p>
    <div class="h-48">
        <canvas id="consentTrendChart"></canvas>
    </div>
</div>

<!-- Pending Requests Queue -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Pending Export Requests</h3>
        </div>
        <div class="p-6">
            @if($pendingRequests->where('type', 'export')->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No pending export requests.</p>
            @else
                <div class="space-y-3">
                    @foreach($pendingRequests->where('type', 'export') as $request)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $request->user_name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->user_email ?? '' }}</p>
                                <p class="text-xs text-gray-400">{{ $request->created_at->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.gdpr.process-export', $request->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-md transition-colors">
                                    Process
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Pending Deletion Requests</h3>
        </div>
        <div class="p-6">
            @if($pendingRequests->where('type', 'deletion')->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No pending deletion requests.</p>
            @else
                <div class="space-y-3">
                    @foreach($pendingRequests->where('type', 'deletion') as $request)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $request->user_name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->user_email ?? '' }}</p>
                                <p class="text-xs text-gray-400">Scheduled: {{ \Carbon\Carbon::parse($request->scheduled_at)->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.gdpr.process-deletion', $request->id) }}" onsubmit="return confirm('Permanently delete user data? This cannot be undone.')">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-md transition-colors">
                                    Process
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Recent Audit Trail -->
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900 dark:text-white">Recent Audit Trail</h3>
        <a href="{{ route('admin.gdpr.audit-log') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View All</a>
    </div>
    <div class="p-6">
        @if($recentAudits->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No audit entries yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Severity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentAudits as $audit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $audit->action }}</td>
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
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $audit->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('consentTrendChart');
        if (!ctx) return;

        const data = @json(array_reverse($consentStats['by_day'] ?? [], true));
        const labels = Object.keys(data);
        const values = Object.values(data);

        if (labels.length === 0) {
            ctx.parentElement.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No data available yet.</p>';
            return;
        }

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Consent Events',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        display: true,
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10, font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>
@endpush
