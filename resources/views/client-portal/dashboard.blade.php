@extends('layouts.unified')

@section('title', 'Client Portal Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Client Portal 2.0</h1>
        <p class="text-gray-600 mt-1">Manage your campaigns, analytics, and invoices in one place</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Campaigns -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Campaigns</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeCampaigns }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $totalCampaigns }} total campaigns</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bullhorn text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Spend -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Spend</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">${{ number_format($totalSpend, 2) }}</p>
                    <p class="text-sm text-gray-500 mt-1">${{ number_format($pendingSpend, 2) }} pending</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Performance Score -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Performance Score</p>
                    <p class="text-3xl font-bold {{ $performanceScore >= 70 ? 'text-green-600' : ($performanceScore >= 40 ? 'text-yellow-600' : 'text-red-600') }} mt-1">{{ $performanceScore }}/100</p>
                    <p class="text-sm text-gray-500 mt-1">{{ number_format($performanceData->total_posts ?? 0) }} posts</p>
                </div>
                <div class="w-12 h-12 {{ $performanceScore >= 70 ? 'bg-green-100' : ($performanceScore >= 40 ? 'bg-yellow-100' : 'bg-red-100') }} rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line {{ $performanceScore >= 70 ? 'text-green-600' : ($performanceScore >= 40 ? 'text-yellow-600' : 'text-red-600') }} text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Clients -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Clients</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeClients }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $totalClients }} total clients</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Invoice Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoices</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Paid</span>
                    <span class="font-semibold text-green-600">{{ $paidInvoices }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Overdue</span>
                    <span class="font-semibold {{ $overdueInvoices > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $overdueInvoices }}</span>
                </div>
            </div>
            <a href="{{ route('client-portal.v2.invoices') }}" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                View all invoices <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <a href="{{ route('client-portal.v2.campaigns') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-bullhorn text-indigo-600 w-6"></i>
                    <span class="ml-2 text-gray-700">View Campaigns</span>
                </a>
                <a href="{{ route('client-portal.v2.analytics') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-chart-bar text-green-600 w-6"></i>
                    <span class="ml-2 text-gray-700">Analytics</span>
                </a>
                <a href="{{ route('client-portal.v2.invoices') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-file-invoice-dollar text-purple-600 w-6"></i>
                    <span class="ml-2 text-gray-700">Invoices</span>
                </a>
            </div>
        </div>

        <!-- Monthly Spend Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Spend</h3>
            @if(count($monthlySpend) > 0)
                <div class="h-32 flex items-end space-x-2">
                    @foreach($monthlySpend as $month => $amount)
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-indigo-500 rounded-t" style="height: {{ min(100, ($amount / max($monthlySpend)) * 100) }}%"></div>
                            <span class="text-xs text-gray-500 mt-1">{{ substr($month, 5) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No spend data available</p>
            @endif
        </div>
    </div>

    <!-- Recent Campaigns Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Campaigns</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentCampaigns as $campaign)
                        <tr>
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900">{{ $campaign->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $campaign->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $campaign->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $campaign->posts_count }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $campaign->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">No campaigns yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-200">
            <a href="{{ route('client-portal.v2.campaigns') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                View all campaigns →
            </a>
        </div>
    </div>
</div>
@endsection
