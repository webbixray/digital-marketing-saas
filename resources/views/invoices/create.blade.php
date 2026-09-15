@extends('layouts.unified')
@section('title', 'Create Invoice')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-10"><div class="card"><div class="card-header"><h3 class="card-title">Create Invoice</h3></div>
    <form action="{{ route('invoices.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                <div class="col-md-6"><div class="form-group"><label>Due Date</label><input type="date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <h5>Line Items</h5>
            <div id="lineItems">
                <div class="row line-item mb-2">
                    <div class="col-md-5"><input type="text" name="items[0][description]" class="form-control" placeholder="Description" required></div>
                    <div class="col-md-3"><input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" value="1" min="0" step="0.01" required></div>
                    <div class="col-md-3"><input type="number" name="items[0][unit_price]" class="form-control" placeholder="Unit Price" value="0" min="0" step="0.01" required></div>
                    <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-times"></i></button></div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-info" id="addItem"><i class="fas fa-plus mr-1"></i> Add Item</button>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('invoices.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
let itemCount = 1;
$('#addItem').on('click', function() {
    const html = `<div class="row line-item mb-2"><div class="col-md-5"><input type="text" name="items[${itemCount}][description]" class="form-control" placeholder="Description" required></div><div class="col-md-3"><input type="number" name="items[${itemCount}][quantity]" class="form-control" placeholder="Qty" value="1" min="0" step="0.01" required></div><div class="col-md-3"><input type="number" name="items[${itemCount}][unit_price]" class="form-control" placeholder="Unit Price" value="0" min="0" step="0.01" required></div><div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-times"></i></button></div></div>`;
    $('#lineItems').append(html);
    itemCount++;
});
$(document).on('click', '.remove-item', function() { $(this).closest('.line-item').remove(); });
</script>
@endpush
