@extends('layouts.unified')
@section('title', 'Invoices')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4">
  <div class="col-span-12 sm:col-span-6 xl:col-span-3">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
      <span class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center"><i class="fas fa-file-invoice-dollar text-blue-600 dark:text-blue-400"></i></span>
      <div><span class="text-xs text-gray-500 dark:text-gray-400">Total Revenue</span><div class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($stats['total'], 2) }}</div></div>
    </div>
  </div>
  <div class="col-span-12 sm:col-span-6 xl:col-span-3">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
      <span class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center"><i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i></span>
      <div><span class="text-xs text-gray-500 dark:text-gray-400">Pending</span><div class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($stats['pending'], 2) }}</div></div>
    </div>
  </div>
  <div class="col-span-12 sm:col-span-6 xl:col-span-3">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
      <span class="w-12 h-12 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i></span>
      <div><span class="text-xs text-gray-500 dark:text-gray-400">Overdue</span><div class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($stats['overdue'], 2) }}</div></div>
    </div>
  </div>
</div>
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-invoice mr-2"></i>Invoices</h3>
    <div class="flex items-center gap-2"><a href="{{ route('invoices.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"><i class="fas fa-plus mr-1"></i> New Invoice</a></div>
  </div>
  <div class="p-0">
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead><tr><th>Invoice #</th><th>Amount</th><th>Status</th><th>Issue Date</th><th>Due Date</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($invoices as $invoice)
          <tr>
            <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
            <td>${{ number_format($invoice->total, 2) }}</td>
            <td><span class="px-2 py-1 text-xs font-medium rounded-full { $invoice->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($invoice->status === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') }">{{ ucfirst($invoice->status) }}</span></td>
            <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
            <td>{{ $invoice->due_date->format('M d, Y') }}</td>
            <td>
              @if($invoice->status === 'pending')
                <form action="{{ route('invoices.paid', $invoice) }}" method="POST" class="inline">
                  @csrf
                  <input type="hidden" name="payment_method" value="manual">
                  <button class="bg-green-600 text-white px-2 py-0.5 rounded hover:bg-green-700 inline-flex items-center gap-1 font-medium transition-colors text-xs"><i class="fas fa-check"></i> Mark Paid</button>
                </form>
              @endif
              <a href="{{ route('invoices.edit', $invoice) }}" class="bg-yellow-500 text-white px-2 py-0.5 rounded hover:bg-yellow-600 inline-flex items-center gap-1 font-medium transition-colors text-xs"><i class="fas fa-edit"></i></a>
              <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-2 py-0.5 rounded hover:bg-red-700 inline-flex items-center gap-1 font-medium transition-colors text-xs" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-gray-500 dark:text-gray-400">No invoices</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </div>
</div>
</div>
@endsection

