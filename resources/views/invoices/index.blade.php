@extends('layouts.unified')
@section('title', 'Invoices')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Invoices</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['count'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-file-invoice text-indigo-600 dark:text-indigo-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['pending'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['overdue'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-file-invoice mr-2"></i>Invoices
            </h3>
            <a href="{{ route('invoices.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
                <i class="fas fa-plus mr-1"></i> New Invoice
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Issue Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $invoice->client->name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${{ number_format($invoice->total, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($invoice->status === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($invoice->status === 'pending')
                                <form action="{{ route('invoices.paid', $invoice) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="payment_method" value="manual">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 inline-flex items-center gap-1 font-medium transition-colors text-xs" title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                                <a href="{{ route('invoices.edit', $invoice) }}" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 inline-flex items-center gap-1 font-medium transition-colors text-xs" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 inline-flex items-center gap-1 font-medium transition-colors text-xs" title="Delete" onclick="return confirm('Delete this invoice?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-file-invoice text-5xl mb-4 block text-gray-300 dark:text-gray-600"></i>
                            <p class="text-sm font-medium">No invoices found</p>
                            <p class="text-xs mt-1"><a href="{{ route('invoices.create') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Create your first invoice</a></p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
