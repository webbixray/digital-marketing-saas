@extends('layouts.unified')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4"><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h3></div>
    <div class="p-6">
        <div class="flex gap-4 mb-4">
            <div class="col-span-12 sm:col-span-6"><h4>{{ config('app.name') }}</h4><p class="text-gray-500 dark:text-gray-400">{{ $invoice->agency?->name }}</p></div>
            <div class="sm:col-span-6 text-right"><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}<br><strong>Status:</strong> <span class="px-2 py-1 text-xs font-medium rounded-full { $invoice->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }">{{ ucfirst($invoice->status) }}</span></div>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200"><thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>${{ number_format($item->unit_price, 2) }}</td><td>${{ number_format($item->total, 2) }}</td></tr>
                @endforeach
                <tr><td colspan="3" class="text-right"><strong>Subtotal</strong></td><td>${{ number_format($invoice->subtotal, 2) }}</td></tr>
                <tr><td colspan="3" class="text-right"><strong>Tax</strong></td><td>${{ number_format($invoice->tax, 2) }}</td></tr>
                <tr><td colspan="3" class="text-right"><strong>Total</strong></td><td><strong>${{ number_format($invoice->total, 2) }}</strong></td></tr>
            </tbody>
        </table></div>
        @if($invoice->notes)<p class="text-gray-500 dark:text-gray-400"><strong>Notes:</strong> {{ $invoice->notes }}</p>@endif
    </div>
</div>
</div>
@endsection

