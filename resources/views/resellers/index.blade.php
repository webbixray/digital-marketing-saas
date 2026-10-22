@extends('layouts.unified')
@section('title', 'Resellers')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Resellers</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your white-label resellers and commissions.</p>
        </div>
        <a href="{{ route('resellers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-plus"></i> Add Reseller
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Resellers</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $resellers->total() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $resellers->where('is_active', true)->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Commissions</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $resellers->sum('pending_commissions_count') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Domains</h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $resellers->where('domain', '!=', null)->count() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Domain</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Commission</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($resellers as $reseller)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $reseller->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $reseller->domain ?? 'N/A' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $reseller->commission_type === 'percentage' ? $reseller->commission_rate . '%' : '$' . number_format($reseller->commission_rate, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="{{ $reseller->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $reseller->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('resellers.show', $reseller) }}" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">View</a>
                            <a href="{{ route('resellers.edit', $reseller) }}" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No resellers yet. Create one to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $resellers->links() }}</div>
    </div>
</div>
@endsection
