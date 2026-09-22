@extends('layouts.unified')

@section('title', 'Invoices')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Invoices</h1>
            <p class="text-gray-600 mt-1">View and manage your invoices</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Outstanding</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">${{ number_format($totalOutstanding, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($totalPaid, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Overdue</p>
                    <p class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $overdueCount }}</p>
                </div>
                <div class="w-12 h-12 {{ $overdueCount > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle {{ $overdueCount > 0 ? 'text-red-600' : 'text-gray-600' }} text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                <a href="{{ route('client-portal.v2.invoices') }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ !request('status') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    All <span class="ml-1 text-xs bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full">{{ array_sum($statusCounts) }}</span>
                </a>
                <a href="{{ route('client-portal.v2.invoices', ['status' => 'pending']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Pending <span class="ml-1 text-xs bg-yellow-100 text-yellow-600 py-0.5 px-2 rounded-full">{{ $statusCounts['pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('client-portal.v2.invoices', ['status' => 'paid']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'paid' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Paid <span class="ml-1 text-xs bg-green-100 text-green-600 py-0.5 px-2 rounded-full">{{ $statusCounts['paid'] ?? 0 }}</span>
                </a>
                <a href="{{ route('client-portal.v2.invoices', ['status' => 'overdue']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'overdue' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Overdue <span class="ml-1 text-xs bg-red-100 text-red-600 py-0.5 px-2 rounded-full">{{ $statusCounts['overdue'] ?? 0 }}</span>
                </a>
                <a href="{{ route('client-portal.v2.invoices', ['status' => 'cancelled']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'cancelled' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Cancelled <span class="ml-1 text-xs bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full">{{ $statusCounts['cancelled'] ?? 0 }}</span>
                </a>
            </nav>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 bg-gray-50">
            <form method="GET" action="{{ route('client-portal.v2.invoices') }}" class="flex flex-col sm:flex-row gap-4">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                
                <div class="w-full sm:w-48">
                    <select name="client_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-40">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           placeholder="From date"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="w-full sm:w-40">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           placeholder="To date"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $invoice->client->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-900">${{ number_format($invoice->total, 2) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $invoice->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $invoice->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                @if($invoice->due_date)
                                    <span class="{{ $invoice->status === 'overdue' ? 'text-red-600 font-medium' : '' }}">
                                        {{ $invoice->due_date->format('M d, Y') }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    @if(in_array($invoice->status, ['pending', 'overdue']))
                                        <button onclick="openPayModal('{{ $invoice->id }}', '{{ $invoice->invoice_number }}', '{{ number_format($invoice->total, 2) }}')"
                                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                                            <i class="fas fa-credit-card mr-1"></i> Pay
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-file-invoice-dollar text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-500 text-lg">No invoices found</p>
                                    <p class="text-gray-400 text-sm mt-1">Invoices will appear here when created</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Pay Invoice Modal -->
<div id="payModal" class="fixed inset-0 bg-gray-900/50 hidden items-center justify-center z-50" x-data="{ open: false }" x-show="open" x-cloak>
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6" @click.outside="open = false">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Pay Invoice</h3>
        <p class="text-gray-600 mb-4">Invoice: <span id="modalInvoiceNumber" class="font-medium"></span></p>
        <p class="text-gray-600 mb-4">Amount: <span id="modalInvoiceAmount" class="font-bold text-lg"></span></p>
        
        <div class="border border-gray-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-gray-500 mb-2">Payment details (demo mode)</p>
            <div class="space-y-3">
                <input type="text" placeholder="Card number" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="4242 4242 4242 4242">
                <div class="flex space-x-3">
                    <input type="text" placeholder="MM/YY" class="w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="12/28">
                    <input type="text" placeholder="CVC" class="w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="123">
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button onclick="closePayModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Cancel
            </button>
            <button onclick="processPayment()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-lock mr-1"></i> Pay Now
            </button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    let currentInvoiceId = null;

    function openPayModal(invoiceId, invoiceNumber, amount) {
        currentInvoiceId = invoiceId;
        document.getElementById('modalInvoiceNumber').textContent = invoiceNumber;
        document.getElementById('modalInvoiceAmount').textContent = '$' + amount;
        const modal = document.getElementById('payModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.__x.$data.open = true;
    }

    function closePayModal() {
        const modal = document.getElementById('payModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.__x.$data.open = false;
        currentInvoiceId = null;
    }

    function processPayment() {
        if (!currentInvoiceId) return;
        
        // Demo: Show success message
        alert('Payment processed successfully! (Demo mode)');
        closePayModal();
    }
</script>
@endsection
