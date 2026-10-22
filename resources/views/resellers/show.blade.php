@extends('layouts.unified')
@section('title', $reseller->name)

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reseller->name }}</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Reseller details, agencies, and commission stats.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('resellers.commissions', $reseller) }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">Commissions</a>
            <a href="{{ route('resellers.settings', $reseller) }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">Settings</a>
            <a href="{{ route('resellers.edit', $reseller) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">Edit</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Earnings</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($stats['total_earnings'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($stats['pending_amount'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">This Month</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($stats['this_month_earnings'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Agencies</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['agency_count'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Reseller Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="font-medium text-gray-500 dark:text-gray-400">Name:</span> <span class="text-gray-900 dark:text-white">{{ $reseller->name }}</span></div>
            <div><span class="font-medium text-gray-500 dark:text-gray-400">Slug:</span> <span class="text-gray-900 dark:text-white">{{ $reseller->slug }}</span></div>
            <div><span class="font-medium text-gray-500 dark:text-gray-400">Domain:</span> <span class="text-gray-900 dark:text-white">{{ $reseller->domain ?? 'N/A' }}</span></div>
            <div><span class="font-medium text-gray-500 dark:text-gray-400">Commission:</span> <span class="text-gray-900 dark:text-white">{{ $reseller->commission_type === 'percentage' ? $reseller->commission_rate . '%' : '$' . number_format($reseller->commission_rate, 2) }} ({{ $reseller->billing_type }})</span></div>
            <div><span class="font-medium text-gray-500 dark:text-gray-400">Status:</span>
                <span class="{{ $reseller->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $reseller->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Commission by Type</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Signup</h5>
                <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($commissionSummary['by_type']['signup'], 2) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Renewal</h5>
                <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($commissionSummary['by_type']['renewal'], 2) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Revenue</h5>
                <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($commissionSummary['by_type']['revenue'], 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Referred Agencies ({{ $agencies->count() }})</h3>
        @if($agencies->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">No agencies referred yet.</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Agency</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($agencies as $agency)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $agency->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $agency->subscription_plan ?? 'free' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="{{ $agency->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($agency->status) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
