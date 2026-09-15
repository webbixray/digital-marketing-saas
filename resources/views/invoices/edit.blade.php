@extends('layouts.unified')
@section('title', 'Edit Invoice')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-10"><div class="card"><div class="card-header"><h3 class="card-title">Edit Invoice {{ $invoice->invoice_number }}</h3></div>
    <form action="{{ route('invoices.update', $invoice) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="row"><div class="col-md-6"><div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" class="form-control" value="{{ $invoice->issue_date->format('Y-m-d') }}" required>
            <div class="col-md-6"><div class="form-group"><label>Due Date</label><input type="date" name="due_date" class="form-control" value="{{ $invoice->due_date->format('Y-m-d') }}" required></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2">{{ $invoice->notes }}</textarea></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

