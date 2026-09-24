@extends('layouts.unified')

@section('title', 'Create Commerce Post')

@section('content')
<div x-data="commerceForm" class="max-w-3xl mx-auto">
    <x-flash-messages />

    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('social-commerce.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Link Product to Social Post</h2>
        </div>
        <p class="text-gray-500 dark:text-gray-400">Connect a published social post with a product for tracking.</p>
    </div>

    <form method="POST" action="{{ route('social-commerce.store') }}" class="space-y-6">
        @csrf

        <!-- Product Picker -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Product</h3>
            <div>
                <select name="product_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Choose a product —</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} @if($product->sku)({{ $product->sku }})@endif — ${{ number_format($product->getEffectivePrice(), 2) }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @if($products->isEmpty())
            <p class="mt-3 text-sm text-amber-600 dark:text-amber-400">
                <i class="fas fa-exclamation-triangle mr-1"></i> No active products available. <a href="{{ route('products.create') }}" class="underline">Create one first</a>.
            </p>
            @endif
        </div>

        <!-- Social Post Picker -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Social Post</h3>
            <div>
                <select name="social_post_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Choose a social post —</option>
                    @foreach($posts as $socialPost)
                        <option value="{{ $socialPost->id }}" {{ old('social_post_id') == $socialPost->id ? 'selected' : '' }}>
                            {{ Str::limit($socialPost->content, 60) }} — {{ ucfirst($socialPost->platform) }} ({{ $socialPost->published_at?->format('M d') }})
                        </option>
                    @endforeach
                </select>
                @error('social_post_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @if($posts->isEmpty())
            <p class="mt-3 text-sm text-amber-600 dark:text-amber-400">
                <i class="fas fa-exclamation-triangle mr-1"></i> No published posts available. Publish a social post first.
            </p>
            @endif
        </div>

        <!-- Commerce Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Commerce Settings</h3>
            <div class="space-y-4">
                <div>
                    <label for="shop_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop URL</label>
                    <input type="url" id="shop_url" name="shop_url" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500" value="{{ old('shop_url') }}" placeholder="https://yourstore.com/product/slug">
                    @error('shop_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="discount_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount Code</label>
                    <input type="text" id="discount_code" name="discount_code" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500" value="{{ old('discount_code') }}" placeholder="SAVE20">
                    @error('discount_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- UTM Parameters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">UTM Parameters</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="utm_source" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                    <input type="text" id="utm_source" name="utm_source" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500" value="{{ old('utm_source', 'social') }}" placeholder="social">
                </div>
                <div>
                    <label for="utm_medium" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Medium</label>
                    <input type="text" id="utm_medium" name="utm_medium" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500" value="{{ old('utm_medium', 'social_commerce') }}" placeholder="social_commerce">
                </div>
                <div>
                    <label for="utm_campaign" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Campaign</label>
                    <input type="text" id="utm_campaign" name="utm_campaign" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500" value="{{ old('utm_campaign') }}" placeholder="summer_sale">
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('social-commerce.index') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Cancel
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-link"></i> Link Product
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('commerceForm', () => ({
        init() {}
    }));
});
</script>
@endpush
@endsection
