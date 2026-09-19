@extends('layouts.unified')
@section('title', 'Billing')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Billing</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your subscription and view invoices.</p>
    </div>

    <!-- Plans -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($plans as $key => $plan)
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 {{ ($agency->subscription_plan ?? 'free') === $key ? 'border-indigo-500 ring-2 ring-indigo-500' : '' }}">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 text-center">
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $plan['name'] }}</h4>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    @if($plan['price'] === 0)
                        Free
                    @else
                        ${{ $plan['price'] }}<small class="text-gray-500 dark:text-gray-400">/mo</small>
                    @endif
                </h2>
            </div>
            <div class="p-6">
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>{{ $plan['features']['posts_per_month'] == -1 ? 'Unlimited' : $plan['features']['posts_per_month'] }} posts/month</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>{{ $plan['features']['ai_generations_per_month'] == -1 ? 'Unlimited' : $plan['features']['ai_generations_per_month'] }} AI generations</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>{{ $plan['features']['social_accounts'] == -1 ? 'Unlimited' : $plan['features']['social_accounts'] }} social accounts</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>{{ $plan['features']['team_members'] == -1 ? 'Unlimited' : $plan['features']['team_members'] }} team members</li>
                </ul>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-center">
                @if(($agency->subscription_plan ?? 'free') === $key)
                    <button class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors opacity-75 cursor-not-allowed" disabled>Current Plan</button>
                @elseif($plan['price'] === 0)
                    <a href="{{ route('billing.checkout', $key) }}" class="border border-indigo-600 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 inline-flex items-center gap-2 font-medium transition-colors">Downgrade</a>
                @else
                    <a href="{{ route('billing.checkout', $key) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Upgrade</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Invoices -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Invoices</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Invoice #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($invoices as $inv)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $inv->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${{ number_format($inv->total, 2) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $invBadge = $inv->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($inv->status === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400');
                                @endphp
                                <span class="{{ $invBadge }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($inv->status) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('billing.invoice.download', $inv) }}" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-1">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoices yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $invoices->links() }}</div>
    </div>
</div>
@endsection
