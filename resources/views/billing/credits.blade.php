@extends('layouts.app')

@section('title', 'Credits & Billing')

@section('head-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .credit-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; padding: 2rem; }
        .transaction-item { transition: background-color 0.2s; }
        .transaction-item:hover { background-color: #f8fafc; }
        .type-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .type-purchase { background-color: #d1fae5; color: #065f46; }
        .type-usage { background-color: #fee2e2; color: #991b1b; }
        .type-refund { background-color: #dbeafe; color: #1e40af; }
        .type-bonus { background-color: #fef3c7; color: #92400e; }
    </style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Credits & Billing</h1>
    <p class="text-gray-600 mb-8">Manage your AI credit balance and view transaction history.</p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Credit Balance Card -->
        <div class="credit-card col-span-1 lg:col-span-2">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-lg font-medium opacity-90 mb-1">Available Balance</h2>
                    <p class="text-4xl font-bold">{{ number_format($balance) }}</p>
                    <p class="text-sm opacity-75 mt-1">credits</p>
                </div>
                <div class="bg-white/20 rounded-lg px-4 py-2">
                    <p class="text-sm opacity-75">Monthly Spend</p>
                    <p class="text-xl font-semibold">{{ number_format($monthlySpend) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-white/20">
                <div>
                    <p class="text-sm opacity-75">Total Purchased</p>
                    <p class="text-lg font-semibold">{{ number_format($summary['total_purchased'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-sm opacity-75">Total Used</p>
                    <p class="text-lg font-semibold">{{ number_format($summary['total_used'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-sm opacity-75">Transactions</p>
                    <p class="text-lg font-semibold">{{ $summary['transaction_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Quick Stats</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 text-sm">Refunded</span>
                    <span class="font-semibold text-blue-600">{{ number_format($summary['total_refunded'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 text-sm">Avg per Transaction</span>
                    <span class="font-semibold text-gray-900">
                        {{ ($summary['transaction_count'] ?? 0) > 0 ? number_format(intval($summary['total_used'] / $summary['transaction_count'])) : 0 }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Purchase Credits</h2>
        <form id="purchaseForm" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (credits)</label>
                <input type="number" id="amount" name="amount" min="100" step="100" value="500"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter amount" required>
            </div>
            <div class="flex-1">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                <input type="text" id="description" name="description"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Reason for purchase">
            </div>
            <div class="flex items-end">
                <button type="submit"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Add Credits
                </button>
            </div>
        </form>
        <div id="purchaseResult" class="mt-3 text-sm hidden"></div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Transaction History</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($transactions as $transaction)
                <div class="transaction-item px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="type-badge type-{{ $transaction->type }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                            <span class="text-gray-700 ml-2">{{ $transaction->description }}</span>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                            </p>
                            <p class="text-sm text-gray-500">Balance: {{ number_format($transaction->balance_after) }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ $transaction->created_at->format('M d, Y H:i') }}</p>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500">No transactions yet. Purchase credits to get started!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('purchaseForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const result = document.getElementById('purchaseResult');
        result.classList.remove('hidden');
        result.textContent = 'Processing...';
        result.className = 'mt-3 text-sm';

        try {
            const response = await fetch('{{ route("billing.credits.purchase") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: parseInt(document.getElementById('amount').value),
                    description: document.getElementById('description').value,
                }),
            });

            const data = await response.json();
            if (data.success) {
                result.textContent = `Success! New balance: ${data.new_balance} credits`;
                result.className = 'mt-3 text-sm text-green-600';
                setTimeout(() => location.reload(), 1500);
            } else {
                result.textContent = data.message || 'Purchase failed';
                result.className = 'mt-3 text-sm text-red-600';
            }
        } catch (err) {
            result.textContent = 'An error occurred. Please try again.';
            result.className = 'mt-3 text-sm text-red-600';
        }
    });
</script>
@endpush
@endsection
