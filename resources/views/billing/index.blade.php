@extends('layouts.unified')
@section('title', 'Billing & Subscription')
@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="text-gray-900 font-medium">Billing</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Billing & Subscription</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your subscription, view invoices, and track usage.</p>
    </div>

    <!-- Current Plan -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-crown text-yellow-500 mr-2"></i>Current Plan
                    </h3>
                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $agency->subscription_status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ ucfirst($agency->subscription_status ?? 'N/A') }}
                    </span>
                </div>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h4 class="text-indigo-600 dark:text-indigo-400 font-semibold text-lg">{{ $plans[$currentPlan]['name'] ?? 'Free' }}</h4>
                            @if(isset($plans[$currentPlan]['price']))
                                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($plans[$currentPlan]['price'], 0) }}<small class="text-gray-500 dark:text-gray-400 text-lg">/mo</small></h2>
                            @else
                                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Free</h2>
                            @endif
                            @if($agency->subscription_start)
                                <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    Started {{ \Carbon\Carbon::parse($agency->subscription_start)->format('M d, Y') }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('billing.upgrade') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                                <i class="fas fa-arrow-circle-up mr-1"></i>
                                {{ $currentPlan === 'free' ? 'Upgrade Plan' : 'Change Plan' }}
                            </a>
                            @if($currentPlan !== 'free')
                                <form method="POST" action="{{ route('billing.cancel-subscription') }}" class="inline" onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors">
                                        <i class="fas fa-times-circle mr-1"></i>Cancel Subscription
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Stats -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-chart-pie text-blue-500 mr-2"></i>Usage This Month
                    </h3>
                </div>
                <div class="p-6">
                    @if(isset($plans[$currentPlan]['features']))
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if(isset($plans[$currentPlan]['features']['posts_per_month']))
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center"><i class="fas fa-pen-fancy text-indigo-600 dark:text-indigo-400"></i></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Social Posts</span>
                                </div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $agency->posts_count ?? 0 }}
                                    <small class="text-sm text-gray-500 dark:text-gray-400">/ {{ $plans[$currentPlan]['features']['posts_per_month'] == -1 ? '∞' : $plans[$currentPlan]['features']['posts_per_month'] }}</small>
                                </div>
                                @if($plans[$currentPlan]['features']['posts_per_month'] != -1)
                                    @php $percent = min(100, (($agency->posts_count ?? 0) / $plans[$currentPlan]['features']['posts_per_month']) * 100); @endphp
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mt-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <small class="text-gray-500 dark:text-gray-400">{{ round($percent) }}% used</small>
                                @endif
                            </div>
                            @endif

                            @if(isset($plans[$currentPlan]['features']['ai_generations_per_month']))
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center"><i class="fas fa-sparkles text-green-600 dark:text-green-400"></i></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">AI Generations</span>
                                </div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $agency->ai_generations_count ?? 0 }}
                                    <small class="text-sm text-gray-500 dark:text-gray-400">/ {{ $plans[$currentPlan]['features']['ai_generations_per_month'] == -1 ? '∞' : $plans[$currentPlan]['features']['ai_generations_per_month'] }}</small>
                                </div>
                                @if($plans[$currentPlan]['features']['ai_generations_per_month'] != -1)
                                    @php $percent = min(100, (($agency->ai_generations_count ?? 0) / $plans[$currentPlan]['features']['ai_generations_per_month']) * 100); @endphp
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mt-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <small class="text-gray-500 dark:text-gray-400">{{ round($percent) }}% used</small>
                                @endif
                            </div>
                            @endif

                            @if(isset($plans[$currentPlan]['features']['team_members']))
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center"><i class="fas fa-users text-yellow-600 dark:text-yellow-400"></i></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Team Members</span>
                                </div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $agency->users_count ?? 0 }}
                                    <small class="text-sm text-gray-500 dark:text-gray-400">/ {{ $plans[$currentPlan]['features']['team_members'] == -1 ? '∞' : $plans[$currentPlan]['features']['team_members'] }}</small>
                                </div>
                                @if($plans[$currentPlan]['features']['team_members'] != -1)
                                    @php $percent = min(100, (($agency->users_count ?? 0) / $plans[$currentPlan]['features']['team_members']) * 100); @endphp
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mt-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <small class="text-gray-500 dark:text-gray-400">{{ round($percent) }}% used</small>
                                @endif
                            </div>
                            @endif
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No usage limits on your current plan.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Plan Summary Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-list-check text-green-500 mr-2"></i>Plan Features</h3>
                </div>
                <div class="p-4">
                    <ul class="space-y-2">
                        @if(isset($plans[$currentPlan]['features']))
                            @foreach($plans[$currentPlan]['features'] as $feature => $limit)
                                <li class="flex justify-between items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300"><i class="fas fa-check text-green-500 mr-2"></i>{{ ucwords(str_replace('_', ' ', $feature)) }}</span>
                                    <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $limit == -1 ? 'Unlimited' : $limit }}</span>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions</h3>
                </div>
                <div class="p-6 space-y-2">
                    <a href="{{ route('billing.upgrade') }}" class="block px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-center font-medium transition-colors">
                        <i class="fas fa-exchange-alt mr-1"></i> Compare Plans
                    </a>
                    <a href="{{ route('agency.invoices') }}" class="block px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-center font-medium transition-colors">
                        <i class="fas fa-file-invoice mr-1"></i> View Invoices
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing History -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-history text-gray-400 mr-2"></i>Recent Billing History
            </h3>
            <a href="{{ route('agency.invoices') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">View All</a>
        </div>
        @if($invoices->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoices->take(5) as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap"><code class="text-sm text-gray-700 dark:text-gray-300">{{ $invoice->invoice_number }}</code></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                $invBadgeClass = match(strtolower($invoice->status)) {
                                    'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                };
                                @endphp
                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $invBadgeClass }}">{{ ucfirst($invoice->status) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('billing.invoice.download', $invoice) }}" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-1">
                                    <i class="fas fa-download mr-1"></i>Download
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-file-invoice-dollar text-5xl mb-4 block text-gray-300 dark:text-gray-600"></i>
                <p class="text-sm font-medium">No billing history yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
