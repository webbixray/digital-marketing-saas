
@extends('layouts.unified')

@section('title', 'Billing Health Monitor')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="text-gray-900 font-medium">Billing Health</li>
@endsection

@section('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .gauge-container {
        position: relative;
        width: 200px;
        height: 100px;
        overflow: hidden;
    }
    .gauge-bg {
        position: absolute;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: conic-gradient(
            from 180deg,
            #ef4444 0deg,
            #f59e0b 60deg,
            #10b981 120deg,
            #10b981 180deg
        );
        mask: radial-gradient(transparent 60%, black 61%);
        -webkit-mask: radial-gradient(transparent 60%, black 61%);
    }
    .gauge-needle {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 4px;
        height: 90px;
        background: #1f2937;
        transform-origin: bottom center;
        transform: translateX(-50%) rotate(-90deg);
        transition: transform 1s ease-out;
        border-radius: 2px;
    }
    .gauge-center {
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 16px;
        height: 16px;
        background: #1f2937;
        border-radius: 50%;
    }
    .metric-card {
        transition: all 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -5px rgba(0,0,0,0.1);
    }
    .pulse-dot {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .chart-wrapper {
        position: relative;
        height: 300px;
    }
</style>
@endsection

@section('content')
<div class="space-y-6" x-data="billingHealth()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-heartbeat text-rose-500 mr-2"></i>Billing Health Monitor
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Real-time revenue metrics, churn analysis & forecasting</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400 dark:text-gray-500" x-text="lastUpdated"></span>
            <button @click="refreshMetrics()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- MRR Gauge + Key Metrics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- MRR Gauge -->
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Monthly Recurring Revenue</h3>
            <div class="gauge-container">
                <div class="gauge-bg"></div>
                <div class="gauge-needle" :style="`transform: translateX(-50%) rotate(${mrrRotation}deg)`"></div>
                <div class="gauge-center"></div>
            </div>
            <div class="text-center mt-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['mrr_formatted'] }}</span>
                <div class="flex items-center justify-center mt-1 gap-1">
                    @if($metrics['mrr_growth'] >= 0)
                        <i class="fas fa-arrow-up text-emerald-500 text-sm"></i>
                        <span class="text-sm font-medium text-emerald-600">+{{ $metrics['mrr_growth'] }}%</span>
                    @else
                        <i class="fas fa-arrow-down text-red-500 text-sm"></i>
                        <span class="text-sm font-medium text-red-600">{{ $metrics['mrr_growth'] }}%</span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs last month</span>
                </div>
            </div>
        </div>

        <!-- Churn Rate -->
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Churn Rate</h3>
                @php
                    $churnColors = [
                        'excellent' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300',
                        'healthy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    ];
                @endphp
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $churnColors[$metrics['churn_status']] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($metrics['churn_status']) }}
                </span>
            </div>
            <div class="flex items-end gap-2">
                <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ $metrics['churn_rate'] }}%</span>
            </div>
            <div class="mt-3">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    @php $churnWidth = min(100, $metrics['churn_rate'] * 10); @endphp
                    <div class="h-2 rounded-full transition-all duration-500
                        {{ $metrics['churn_status'] === 'excellent' ? 'bg-emerald-500' : '' }}
                        {{ $metrics['churn_status'] === 'healthy' ? 'bg-green-500' : '' }}
                        {{ $metrics['churn_status'] === 'warning' ? 'bg-yellow-500' : '' }}
                        {{ $metrics['churn_status'] === 'critical' ? 'bg-red-500' : '' }}"
                        style="width: {{ $churnWidth }}%"></div>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $metrics['cancelled_count'] }} cancellations this month</p>
        </div>

        <!-- Overdue Invoices -->
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Overdue</h3>
                @if($metrics['overdue_count'] > 0)
                    <span class="pulse-dot w-2 h-2 rounded-full bg-red-500"></span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ $metrics['overdue_count'] }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">invoices</span>
            </div>
            <p class="text-lg font-semibold text-red-600 dark:text-red-400 mt-2">{{ $metrics['overdue_amount_formatted'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Outstanding past due date</p>
        </div>

        <!-- Collected This Month -->
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Collected</h3>
                <span class="pulse-dot w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="flex items-end gap-2">
                <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ $metrics['collected_formatted'] }}</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                <span class="font-medium">{{ $metrics['pending_count'] }}</span> pending
                <span class="text-gray-400 mx-1">|</span>
                <span class="font-medium">{{ $metrics['pending_formatted'] }}</span>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This month's collection</p>
        </div>
    </div>

    <!-- ARR + Outstanding Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Annual Run Rate</h3>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $forecast['arr_formatted'] }}</span>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Projected annual revenue at current MRR</p>
        </div>
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Avg Invoice Value</h3>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['avg_invoice_formatted'] }}</span>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Average paid invoice amount</p>
        </div>
        <div class="metric-card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Total Revenue</h3>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['total_revenue_formatted'] }}</span>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lifetime collected revenue</p>
        </div>
    </div>

    <!-- Revenue Forecast Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-chart-line text-indigo-500 mr-2"></i>Revenue Forecast
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Growth rate: <span class="font-medium {{ $forecast['growth_rate'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $forecast['growth_rate'] }}%</span>
                </span>
                <select @change="updateForecast($event.target.value)" class="text-xs bg-gray-100 dark:bg-gray-700 border-none rounded-lg px-2 py-1">
                    <option value="3">3 months</option>
                    <option value="6" selected>6 months</option>
                    <option value="12">12 months</option>
                </select>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="revenueForecastChart"></canvas>
        </div>
    </div>

    <!-- Overdue Alerts & Payment Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Overdue Invoice Alerts -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Overdue Invoice Alerts
            </h3>
            @if(count($overdueInvoices) > 0)
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($overdueInvoices as $inv)
                        <div class="flex items-center justify-between p-3 rounded-lg border
                            {{ $inv['severity'] === 'critical' ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : '' }}
                            {{ $inv['severity'] === 'warning' ? 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20' : '' }}
                            {{ $inv['severity'] === 'info' ? 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50' : '' }}">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $inv['client_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $inv['invoice_number'] }} &middot; Due {{ $inv['due_date'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $inv['amount_formatted'] }}</p>
                                <p class="text-xs
                                    {{ $inv['severity'] === 'critical' ? 'text-red-600' : '' }}
                                    {{ $inv['severity'] === 'warning' ? 'text-yellow-600' : '' }}
                                    {{ $inv['severity'] === 'info' ? 'text-gray-500' : '' }}">
                                    {{ $inv['days_overdue'] }} days overdue
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-4xl text-emerald-400 mb-3"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No overdue invoices. Great job!</p>
                </div>
            @endif
        </div>

        <!-- Payment Timeline -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-clock text-emerald-500 mr-2"></i>Recent Payments
            </h3>
            @if(count($paymentTimeline) > 0)
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($paymentTimeline as $payment)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-emerald-600 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $payment['client_name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment['invoice_number'] }} &middot; {{ $payment['paid_date'] }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $payment['amount_formatted'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($payment['days_to_pay'] !== null)
                                        {{ $payment['days_to_pay'] }} days to pay
                                    @else
                                        {{ $payment['payment_method'] }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-file-invoice-dollar text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No payments recorded yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Plan Distribution -->
    @if(count($planDistribution) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-pie-chart text-purple-500 mr-2"></i>Plan Distribution
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($planDistribution as $plan)
                    <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($plan['plan']) }}</p>
                        <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $plan['count'] }} clients</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $plan['revenue_formatted'] }}/mo</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function billingHealth() {
    return {
        mrrRotation: -90,
        lastUpdated: 'Just now',
        chart: null,

        init() {
            this.$nextTick(() => {
                this.initChart();
                this.updateGauge();
            });
        },

        updateGauge() {
            // Rotate needle based on MRR (max $100k = 90deg)
            const mrr = {{ $metrics['mrr'] }};
            const maxMRR = 100000;
            const normalized = Math.min(mrr / maxMRR, 1);
            this.mrrRotation = -90 + (normalized * 180);
        },

        initChart() {
            const ctx = document.getElementById('revenueForecastChart');
            if (!ctx) return;

            const forecastData = @json($forecast);

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: forecastData.labels,
                    datasets: [
                        {
                            label: 'Historical Revenue',
                            data: forecastData.historical,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            spanGaps: false,
                        },
                        {
                            label: 'Projected Revenue',
                            data: forecastData.projected,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0.3,
                            fill: true,
                            spanGaps: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y?.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        },

        updateForecast(months) {
            fetch(`{{ route('billing.health.forecast') }}?months=${months}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && this.chart) {
                        this.chart.data.labels = data.data.labels;
                        this.chart.data.datasets[0].data = data.data.historical;
                        this.chart.data.datasets[1].data = data.data.projected;
                        this.chart.update();
                        this.lastUpdated = 'Updated ' + new Date().toLocaleTimeString();
                    }
                });
        },

        refreshMetrics() {
            fetch(`{{ route('billing.health.metrics') }}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.lastUpdated = 'Updated ' + new Date().toLocaleTimeString();
                        // Page reload to show fresh data (simpler than dynamic Alpine updates)
                        window.location.reload();
                    }
                });
        }
    };
}
</script>
@endpush
