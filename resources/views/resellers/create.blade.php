@extends('layouts.unified')
@section('title', 'Create Reseller')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Reseller</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Add a new white-label reseller partner.</p>
    </div>

    <form method="POST" action="{{ route('resellers.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Domain</label>
                    <input type="text" name="domain" value="{{ old('domain') }}" placeholder="brand.example.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('domain')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Primary Color</label>
                    <input type="text" name="primary_color" value="{{ old('primary_color', '#0d6efd') }}" placeholder="#0d6efd" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('primary_color')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                    <input type="url" name="logo_url" value="{{ old('logo_url') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('logo_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Commission Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commission Type</label>
                    <select name="commission_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="percentage" {{ old('commission_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    </select>
                    @error('commission_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commission Rate</label>
                    <input type="number" name="commission_rate" value="{{ old('commission_rate', 20) }}" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('commission_rate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Type</label>
                    <select name="billing_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="revenue_share" {{ old('billing_type') === 'revenue_share' ? 'selected' : '' }}>Revenue Share</option>
                        <option value="monthly" {{ old('billing_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                    @error('billing_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create Reseller</button>
            <a href="{{ route('resellers.index') }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">Cancel</a>
        </div>
    </form>
</div>
@endsection
