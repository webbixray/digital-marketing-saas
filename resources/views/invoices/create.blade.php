@extends('layouts.unified')
@section('title', 'Create Invoice')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4"><div class="md:col-span-10"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Create Invoice</h3></div>
    <form action="{{ route('invoices.store') }}" method="POST">@csrf
        <div class="p-6">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>Issue Date</label><input type="date" name="issue_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ date('Y-m-d') }}" required></div></div>
                <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>Due Date</label><input type="date" name="due_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required></div></div>
            </div>
            <div class="mb-4"><label>Notes</label><textarea name="notes" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="2"></textarea></div>
            <h5>Line Items</h5>
            <div id="lineItems">
                <div class="flex gap-4 line-item mb-2">
                    <div class="md:col-span-5"><input type="text" name="items[0][description]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Description" required></div>
                    <div class="col-span-12 md:col-span-3"><input type="number" name="items[0][quantity]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Qty" value="1" min="0" step="0.01" required></div>
                    <div class="col-span-12 md:col-span-3"><input type="number" name="items[0][unit_price]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Unit Price" value="0" min="0" step="0.01" required></div>
                    <div class="md:col-span-1"><button type="button" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors text-xs remove-item"><i class="fas fa-times"></i></button></div>
                </div>
            </div>
            <button type="button" class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 inline-flex items-center gap-1 font-medium transition-colors text-sm" id="addItem"><i class="fas fa-plus mr-1"></i> Add Item</button>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create</button> <a href="{{ route('invoices.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors">Cancel</a></div>
    </form>
</div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemCount = 1;
        const addBtn = document.getElementById('addItem');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                const html = `<div class="flex gap-4 line-item mb-2">
                    <div class="md:col-span-5"><input type="text" name="items[${itemCount}][description]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Description" required></div>
                    <div class="col-span-12 md:col-span-3"><input type="number" name="items[${itemCount}][quantity]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Qty" value="1" min="0" step="0.01" required></div>
                    <div class="col-span-12 md:col-span-3"><input type="number" name="items[${itemCount}][unit_price]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Unit Price" value="0" min="0" step="0.01" required></div>
                    <div class="md:col-span-1"><button type="button" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors text-xs remove-item"><i class="fas fa-times"></i></button></div>
                </div>`;
                document.getElementById('lineItems').insertAdjacentHTML('beforeend', html);
                itemCount++;
            });
        }
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                e.target.closest('.line-item').remove();
            }
        });
    });
</script>
@endpush
