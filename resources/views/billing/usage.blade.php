@extends('layouts.app')

@section('title', 'Usage Dashboard')

@section('head-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .quota-bar { height: 8px; border-radius: 9999px; background-color: #e5e7eb; overflow: hidden; }
        .quota-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.3s; }
        .quota-safe { background-color: #10b981; }
        .quota-warning { background-color: #f59e0b; }
        .quota-exceeded { background-color: #ef4444; }
        .usage-card { transition: transform 0.2s; }
        .usage-card:hover { transform: translateY(-2px); }
    </style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Usage Dashboard</h1>
    <p class="text-gray-600 mb-8">Track metered usage, quota consumption, and billing trends.</p>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="usage-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-sm text-gray-500 mb-1">Current Bill</p>
            <p class="text-2xl font-bold text-gray-900">${{ number_format($currentBill ?? 0, 4) }}</p>
            <p class="text-xs text-gray-400 mt-1">This month</p>
        </div>
        <div class="usage-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-sm text-gray-500 mb-1">Monthly Spend</p>
            <p class="text-2xl font-bold text-indigo-600">{{ number_format($monthlySpend ?? 0) }} credits</p>
            <p class="text-xs text-gray-400 mt-1">Total consumed</p>
        </div>
        <div class="usage-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-sm text-gray-500 mb-1">Total Records</p>
            <p class="text-2xl font-bold text-gray-900">{{ $usageSummary['total_records'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Usage events</p>
        </div>
        <div class="usage-card bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-sm text-gray-500 mb-1">Quota Alerts</p>
            <p class="text-2xl font-bold {{ ($quotaStatus['exceeded_count'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $quotaStatus['exceeded_count'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Exceeded quotas</p>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Usage by Metric</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <canvas id="usagePriceChart" height="200"></canvas>
            </div>
            <div>
                <canvas id="usageQuantityChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Bill Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Current Bill Breakdown</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-4 font-semibold text-gray-700">Metric</th>
                        <th class="text-right py-2 px-4 font-semibold text-gray-700">Quantity</th>
                        <th class="text-right py-2 px-4 font-semibold text-gray-700">Unit Price</th>
                        <th class="text-right py-2 px-4 font-semibold text-gray-700">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($billItems ?? [] as $item)
                        <tr>
                            <td class="py-3 px-4">{{ Str::title(str_replace('_', ' ', $item['metric'])) }}</td>
                            <td class="py-3 px-4 text-right">{{ number_format($item['quantity'], 4) }}</td>
                            <td class="py-3 px-4 text-right">${{ number_format($item['unit_price'], 8) }}</td>
                            <td class="py-3 px-4 text-right font-semibold">${{ number_format($item['total_price'], 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-gray-500">No usage recorded this month.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-300">
                        <td colspan="3" class="py-3 px-4 font-semibold text-right">Total</td>
                        <td class="py-3 px-4 text-right font-bold text-lg">${{ number_format($currentBill ?? 0, 4) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Quota Status -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Quota Status</h2>
        <div class="space-y-4">
            @forelse($quotaStatus['quotas'] ?? [] as $quota)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <span class="font-semibold text-gray-900">{{ Str::title(str_replace('_', ' ', $quota['metric'])) }}</span>
                            <span class="text-xs text-gray-500 ml-2">({{ $quota['period'] }})</span>
                        </div>
                        <span class="text-sm {{ $quota['exceeded'] ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                            {{ number_format($quota['used']) }} / {{ number_format($quota['limit']) }}
                        </span>
                    </div>
                    <div class="quota-bar">
                        <div class="quota-bar-fill {{ $quota['usage_percent'] >= 100 ? 'quota-exceeded' : ($quota['usage_percent'] >= 80 ? 'quota-warning' : 'quota-safe') }}"
                            style="width: {{ min($quota['usage_percent'], 100) }}%"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">{{ number_format($quota['remaining']) }} remaining</span>
                        <span class="text-xs text-gray-500">{{ number_format($quota['usage_percent']) }}%</span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-8">No quotas configured. Set quotas to track usage limits.</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    @if(!empty($usageSummary['by_metric']))
    const metrics = @json(array_keys($usageSummary['by_metric']));
    const prices = @json(array_map(fn($m) => $m['total_price'], $usageSummary['by_metric']));
    const quantities = @json(array_map(fn($m) => $m['total_quantity'], $usageSummary['by_metric']));

    const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

    new Chart(document.getElementById('usagePriceChart'), {
        type: 'doughnut',
        data: {
            labels: metrics.map(m => m.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
            datasets: [{
                data: prices,
                backgroundColor: colors.slice(0, metrics.length),
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' }, title: { display: true, text: 'Price by Metric ($)' } } }
    });

    new Chart(document.getElementById('usageQuantityChart'), {
        type: 'bar',
        data: {
            labels: metrics.map(m => m.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())),
            datasets: [{
                label: 'Quantity',
                data: quantities,
                backgroundColor: '#6366f1',
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false }, title: { display: true, text: 'Quantity by Metric' } }, y: { beginAtZero: true } }
    });
    @endif
</script>
@endpush
@endsection
