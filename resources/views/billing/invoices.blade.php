@extends('layouts.unified')

@section('title', 'Invoices')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('agency.billing') }}">Billing</a></li>
    <li class="breadcrumb-item active">Invoices</li>
@endsection

@section('content')
<div class="space-y-6">
<div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-file-invoice-dollar text-secondary mr-2"></i>Billing Invoices
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white float-right" placeholder="Search invoices..." id="invoiceSearch">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                @if($invoices->count())
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50" id="invoicesTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td>
                                        <code class="text-primary">{{ $inv->invoice_number }}</code>
                                        @if($inv->client)
                                            <br><small class="text-muted">{{ $inv->client->name ?? '—' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inv->issue_date)
                                            {{ \Carbon\Carbon::parse($inv->issue_date)->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inv->due_date)
                                            @php
                                                $dueDate = \Carbon\Carbon::parse($inv->due_date);
                                                $isOverdue = $inv->status !== 'paid' && $dueDate->isPast();
                                            @endphp
                                            <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $dueDate->format('M d, Y') }}
                                                @if($isOverdue)
                                                    <i class="fas fa-exclamation-triangle ml-1"></i>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $inv->currency }} {{ number_format($inv->total, 2) }}</strong>
                                        @if($inv->subtotal != $inv->total)
                                            <br><small class="text-muted">Sub: {{ $inv->currency }} {{ number_format($inv->subtotal, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match(strtolower($inv->status)) {
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'overdue' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'info'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }} badge-pill">
                                            {{ ucfirst($inv->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($inv->payment_method)
                                            <small class="text-muted">{{ ucfirst($inv->payment_method) }}</small>
                                            @if($inv->transaction_id)
                                                <br><code class="text-xs">{{ Str::limit($inv->transaction_id, 12) }}</code>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('billing.invoice.download', $inv) }}" class="btn btn-sm btn-outline-primary" title="Download Invoice">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @if($inv->status === 'pending')
                                            <form method="POST" action="{{ route('invoices.paid', $inv) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as Paid">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-file-invoice fa-3x mb-3 d-block"></i>
                        <h5>No invoices yet</h5>
                        <p>Invoices will appear here once you have billing activity.</p>
                        <a href="{{ route('billing.upgrade') }}" class="btn btn-primary mt-2">
                            <i class="fas fa-arrow-circle-up mr-1"></i>Upgrade to get started
                        </a>
                    </div>
                @endif
            </div>
            @if($invoices->count())
                <div class="card-footer clearfix">
                    <div class="float-left">
                        Showing {{ $invoices->firstItem() }} - {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                    </div>
                    <div class="float-right">
                        {{ $invoices->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Summary Cards -->
@if($invoices->count())
<div class="row mt-3">
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $invoices->where('status', 'paid')->count() }}</h3>
                <p>Paid Invoices</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $invoices->where('status', 'pending')->count() }}</h3>
                <p>Pending Invoices</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $invoices->where('status', 'overdue')->count() }}</h3>
                <p>Overdue Invoices</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $invoices->first()->currency }} {{ number_format($invoices->sum('total'), 2) }}</h3>
                <p>Total Billed</p>
            </div>
            <div class="icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
</div>
@endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const search = document.getElementById('invoiceSearch');
        if (search) {
            search.addEventListener('input', () => dmsaas.filterTable(search, '#invoicesTable'));
        }
    });
</script>
@endpush
