@extends('layouts.unified')
@section('title', $reseller->name . ' - Commissions')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reseller->name }} - Commissions</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">View commission history and process payouts.</p>
        </div>
        <a href="{{ route('resellers.show', $reseller) }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">Back to Reseller</a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('resellers.commissions', $reseller) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Range</label>
                <select name="date_filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="all" {{ request('date_filter') == 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ request('date_filter') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ request('date_filter') == 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="reversed" {{ request('status') == 'reversed' ? 'selected' : '' }}>Reversed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Types</option>
                    <option value="signup" {{ request('type') == 'signup' ? 'selected' : '' }}>Signup</option>
                    <option value="renewal" {{ request('type') == 'renewal' ? 'selected' : '' }}>Renewal</option>
                    <option value="revenue" {{ request('type') == 'revenue' ? 'selected' : '' }}>Revenue</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">Filter</button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Earnings</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($commissionHistory->sum('amount'), 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</h4>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-2">${{ number_format($pendingCommissions->sum('amount'), 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid Out</h4>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">${{ number_format($commissionHistory->where('status', 'paid')->sum('amount'), 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Commission Rate</h4>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">{{ $reseller->commission_rate }}%</p>
        </div>
    </div>

    <!-- Pending Payouts -->
    @if($pendingCommissions->count() > 0)
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Pending Payouts</h3>
        <div class="space-y-3">
            @foreach($pendingCommissions as $commission)
            <div class="flex items-center justify-between border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">${{ number_format($commission->amount, 2) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($commission->type) }} - {{ $commission->created_at->format('M d, Y') }}</p>
                </div>
                <form method="POST" action="{{ route('resellers.commissions.pay', ['reseller' => $reseller, 'commission' => $commission]) }}">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium transition-colors">Mark Paid</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Commission History Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Commission History</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Paid At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @forelse($commissionHistory as $commission)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="py-3 px-4 text-gray-900 dark:text-white">{{ $commission->created_at->format('M d, Y') }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                {{ $commission->type == 'signup' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                {{ $commission->type == 'renewal' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}
                                {{ $commission->type == 'revenue' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}">
                                {{ ucfirst($commission->type) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">${{ number_format($commission->amount, 2) }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                {{ $commission->status == 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                {{ $commission->status == 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                {{ $commission->status == 'reversed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $commission->paid_at ? $commission->paid_at->format('M d, Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">No commission history found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
