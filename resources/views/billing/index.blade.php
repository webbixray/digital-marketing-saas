@extends('layouts.unified')

@section('title', 'Billing & Subscription')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="text-gray-900 font-medium">Billing</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
<!-- Current Plan -->
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-crown text-warning mr-2"></i>Current Plan
                </h3>
                <span class="{{ $agency->subscription_status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-sm font-medium px-3 py-1 rounded-full">
                        {{ ucfirst($agency->subscription_status ?? 'N/A') }}
                    </span>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-12 gap-4>
                    <div class="col-span-12 md:col-span-6">
                        <h4 class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $plans[$currentPlan]['name'] ?? 'Free' }}</h4>
                        @if(isset($plans[$currentPlan]['price']))
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($plans[$currentPlan]['price'], 0) }}<small class="text-gray-500 dark:text-gray-400">/mo</small></h2>
                        @else
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Free</h2>
                        @endif
                        @if($agency->subscription_start)
                            <p class="text-gray-500 dark:text-gray-400 mt-2">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    Started {{ \Carbon\Carbon::parse($agency->subscription_start)->format('M d, Y') }}
                                </small>
                            </p>
                        @endif
                    </div>
                    <div class="md:col-span-6 text-right">
                        <a href="{{ route('billing.upgrade') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                            <i class="fas fa-arrow-circle-up mr-1"></i>
                            {{ $currentPlan === 'free' ? 'Upgrade Plan' : 'Change Plan' }}
                        </a>
                        @if($currentPlan !== 'free')
                            <form method="POST" action="{{ route('billing.cancel-subscription') }}" class="inline" onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                                @csrf
                                <button type="submit" class="px-4 py-2 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors text-lg mt-2">
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
                    <i class="fas fa-chart-pie text-info mr-2"></i>Usage This Month
                </h3>
            </div>
            <div class="p-6">
                @if(isset($plans[$currentPlan]['features']))
                    <div class="grid grid-cols-12 gap-4>
                        @if(isset($plans[$currentPlan]['features']['posts_per_month']))
                        <div class="col-span-12 md:col-span-4">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2"><span class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center"><i class="fas fa-pen-fancy text-indigo-600 dark:text-indigo-400"></i></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Social Posts</span></div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $agency->posts_count ?? 0 }}
                                        <small>/ {{ $plans[$currentPlan]['features']['posts_per_month'] == -1 ? '∞' : $plans[$currentPlan]['features']['posts_per_month'] }}</small>
                                    </span>
                                    @if($plans[$currentPlan]['features']['posts_per_month'] != -1)
                                        @php
                                            $percent = min(100, (($agency->posts_count ?? 0) / $plans[$currentPlan]['features']['posts_per_month']) * 100);
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-gray-500 dark:text-gray-400">{{ round($percent) }}% used</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($plans[$currentPlan]['features']['ai_generations_per_month']))
                        <div class="col-span-12 md:col-span-4">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2"><span class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center"><i class="fas fa-sparkles text-green-600 dark:text-green-400"></i></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">AI Generations</span></div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $agency->ai_generations_count ?? 0 }}
                                        <small>/ {{ $plans[$currentPlan]['features']['ai_generations_per_month'] == -1 ? '∞' : $plans[$currentPlan]['features']['ai_generations_per_month'] }}</small>
                                    </span>
                                    @if($plans[$currentPlan]['features']['ai_generations_per_month'] != -1)
                                        @php
                                            $percent = min(100, (($agency->ai_generations_count ?? 0) / $plans[$currentPlan]['features']['ai_generations_per_month']) * 100);
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-gray-500 dark:text-gray-400">{{ round($percent) }}% used</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($plans[$currentPlan]['features']['team_members']))
                        <div class="col-span-12 md:col-span-4">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Team Members</span>
                                    <span class="info-box-number">
                                        {{ $agency->users_count ?? 0 }}
                                        <small>/ {{ $plans[$currentPlan]['features']['team_members'] == -1 ? '∞' : $plans[$currentPlan]['features']['team_members'] }}</small>
                                    </span>
                                    @if($plans[$currentPlan]['features']['team_members'] != -1)
                                        @php
                                            $percent = min(100, (($agency->users_count ?? 0) / $plans[$currentPlan]['features']['team_members']) * 100);
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                            <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-gray-500 dark:text-gray-400">{{ round($percent) }}% used</small>
                                    @endif
                                </div>
                            </div>
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
    <div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-list-check text-success mr-2"></i>Plan Features</h3>
            </div>
            <div class="p-0">
                <ul class="space-y-2 p-4">
                    @if(isset($plans[$currentPlan]['features']))
                        @foreach($plans[$currentPlan]['features'] as $feature => $limit)
                            <li class="flex justify-between items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                <span><i class="fas fa-check text-success mr-2"></i>{{ ucwords(str_replace('_', ' ', $feature)) }}</span>
                                <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $limit == -1 ? 'Unlimited' : $limit }}</span>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-bolt text-warning mr-2"></i>Quick Actions</h3>
            </div>
            <div class="p-6">
                <a href="{{ route('billing.upgrade') }}" class="block px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-center font-medium transition-colors">
                    <i class="fas fa-exchange-alt mr-1"></i> Compare Plans
                </a>
                <a href="{{ route('agency.invoices') }}" class="block px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-center font-medium transition-colors mt-2">
                    <i class="fas fa-file-invoice mr-1"></i> View Invoices
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Billing History -->
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-history text-gray-400 mr-2"></i>Recent Billing History
                </h3>
                <a href="{{ route('agency.invoices') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">View All</a>
            </div>
            <div class="p-0">
                @if($invoices->count())
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices->take(5) as $invoice)
                                <tr>
                                    <td><code>{{ $invoice->invoice_number }}</code></td>
                                    <td>{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('M d, Y') : '—' }}</td>
                                    <td><strong>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</strong></td>
                                    <td>
                                        @php
                                        $invBadgeClass = match(strtolower($invoice->status)) {
                                            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                        };
                                        @endphp
                                        <span class="{{ $invBadgeClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('billing.invoice.download', $invoice) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-1">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @else
                    <div class="text-center py-5 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-invoice fa-3x mb-3 d-block"></i>
                        <p>No billing history yet.</p>
                    </div>
                @endif
            </div>
            @if($invoices->count())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection