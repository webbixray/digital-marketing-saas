@extends('layouts.unified')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Invoice {{ $invoice->invoice_number }}</h3></div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-sm-6"><h4>{{ config('app.name') }}</h4><p class="text-muted">{{ $invoice->agency?->name }}</p></div>
            <div class="col-sm-6 text-right"><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}<br><strong>Status:</strong> <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($invoice->status) }}</span></div>
        </div>
        <table class="table table-bordered"><thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>${{ number_format($item->unit_price, 2) }}</td><td>${{ number_format($item->total, 2) }}</td></tr>
                @endforeach
                <tr><td colspan="3" class="text-right"><strong>Subtotal</strong></td><td>${{ number_format($invoice->subtotal, 2) }}</td></tr>
                <tr><td colspan="3" class="text-right"><strong>Tax</strong></td><td>${{ number_format($invoice->tax, 2) }}</td></tr>
                <tr><td colspan="3" class="text-right"><strong>Total</strong></td><td><strong>${{ number_format($invoice->total, 2) }}</strong></td></tr>
            </tbody>
        </table>
        @if($invoice->notes)<p class="text-muted"><strong>Notes:</strong> {{ $invoice->notes }}</p>@endif
    </div>
</div>
</div>
@endsection

