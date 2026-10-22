@extends('layouts.unified')

@section('title', 'Invoices')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="hover:text-gray-700"><a href="{{ route('agency.billing') }}">Billing</a></li>
    <li class="text-gray-900 font-medium">Invoices</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Invoices</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View and download your billing invoices.</p>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-file-invoice-dollar text-gray-400 mr-2"></i>Billing Invoices
            </h3>
        </div>
        <div class="p-6">
            @if($invoices->count())
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Invoice #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Issue Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($invoices as $inv)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    <code class="text-indigo-600 dark:text-indigo-400">{{ $inv->invoice_number }}</code>
                                    @if($inv->client)
                                        <br><small class="text-gray-500 dark:text-gray-400">{{ $inv->client->name ?? '—' }}</small>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($inv->issue_date)
                                        {{ \Carbon\Carbon::parse($inv->issue_date)->format('M d, Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($inv->due_date)
                                        @php
                                            $dueDate = \Carbon\Carbon::parse($inv->due_date);
                                            $isOverdue = $inv->status !== 'paid' && $dueDate->isPast();
                                        @endphp
                                        <span class="{{ $isOverdue ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                                            {{ $dueDate->format('M d, Y') }}
                                            @if($isOverdue)
                                                <i class="fas fa-exclamation-triangle ml-1"></i>
                                            @endif
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    <strong>{{ $inv->currency }} {{ number_format($inv->total, 2) }}</strong>
                                    @if($inv->subtotal != $inv->total)
                                        <br><small class="text-gray-500 dark:text-gray-400">Sub: {{ $inv->currency }} {{ number_format($inv->subtotal, 2) }}</small>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeClass = match(strtolower($inv->status)) {
                                            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                        };
                                    @endphp
                                    <span class="{{ $badgeClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($inv->status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($inv->payment_method)
                                        <small>{{ ucfirst($inv->payment_method) }}</small>
                                        @if($inv->transaction_id)
                                            <br><code class="text-xs">{{ Str::limit($inv->transaction_id, 12) }}</code>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('billing.invoice.download', $inv) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-1" title="Download Invoice">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @if($inv->status === 'pending')
                                        <form method="POST" action="{{ route('invoices.paid', $inv) }}" class="inline ml-2">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 border border-green-300 dark:border-green-600 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 text-sm font-medium transition-colors" title="Mark as Paid">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-file-invoice text-5xl mb-4 block text-gray-300 dark:text-gray-600"></i>
                    <h5 class="text-lg font-medium">No invoices yet</h5>
                    <p>Invoices will appear here once you have billing activity.</p>
                    <a href="{{ route('billing.upgrade') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors mt-4">
                        <i class="fas fa-arrow-circle-up mr-1"></i>Upgrade to get started
                    </a>
                </div>
            @endif
        </div>
        @if($invoices->count())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ $invoices->firstItem() }} - {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                </div>
                <div>
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Summary Cards -->
    @if($invoices->count())
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <div class="flex items-center gap-3"><i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl"></i><div><h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $invoices->where('status', 'paid')->count() }}</h3><p class="text-sm text-gray-500 dark:text-gray-400">Paid Invoices</p></div></div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <div class="flex items-center gap-3"><i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-2xl"></i><div><h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $invoices->where('status', 'pending')->count() }}</h3><p class="text-sm text-gray-500 dark:text-gray-400">Pending Invoices</p></div></div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <div class="flex items-center gap-3"><i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-2xl"></i><div><h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $invoices->where('status', 'overdue')->count() }}</h3><p class="text-sm text-gray-500 dark:text-gray-400">Overdue Invoices</p></div></div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-center gap-3"><i class="fas fa-dollar-sign text-blue-600 dark:text-blue-400 text-2xl"></i><div><h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $invoices->first()->currency }} {{ number_format($invoices->sum('total'), 2) }}</h3><p class="text-sm text-gray-500 dark:text-gray-400">Total Billed</p></div></div>
        </div>
    </div>
    @endif
</div>
@endsection
