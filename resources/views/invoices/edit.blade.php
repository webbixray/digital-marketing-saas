@extends('layouts.unified')
@section('title', 'Edit Invoice')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Edit Invoice {{ $invoice->invoice_number }}</h3></div>
            <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                @csrf @method('PUT')
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Issue Date</label>
                            <input type="date" name="issue_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $invoice->issue_date->format('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                            <input type="date" name="due_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $invoice->due_date->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="2">{{ $invoice->notes }}</textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Update</button>
                    <a href="{{ route('invoices.show', $invoice) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection