@extends('layouts.unified')
@section('title', 'Invoices')
@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="info-box"><span class="info-box-icon bg-info"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content"><span class="info-box-text">Total Revenue</span><span class="info-box-number">${{ number_format($stats['total'], 2) }}</span></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
            <div class="info-box-content"><span class="info-box-text">Pending</span><span class="info-box-number">${{ number_format($stats['pending'], 2) }}</span></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content"><span class="info-box-text">Overdue</span><span class="info-box-number">${{ number_format($stats['overdue'], 2) }}</span></div>
        </div>
    </div>
</div>
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-invoice mr-2"></i>Invoices</h3>
        <div class="card-tools"><a href="{{ route('invoices.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"><i class="fas fa-plus mr-1"></i> New Invoice</a></div>
    </div>
    <div class="card-body p-0">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead><tr><th>Invoice #</th><th>Amount</th><th>Status</th><th>Issue Date</th><th>Due Date</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                        <td>${{ number_format($invoice->total, 2) }}</td>
                        <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">{{ ucfirst($invoice->status) }}</span></td>
                        <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                        <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                        <td>
                            @if($invoice->status === 'pending')
                                <form action="{{ route('invoices.paid', $invoice) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="payment_method" value="manual">
                                    <button class="btn btn-xs btn-success"><i class="fas fa-check"></i> Mark Paid</button>
                                </form>
                            @endif
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No invoices</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
</div>
@endsection

