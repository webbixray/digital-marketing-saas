@extends('layouts.unified')

@section('title', $product->name)

@section('content')
<div x-data="productDetail">
    <x-flash-messages />

    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">SKU: {{ $product->sku ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('products.edit', $product) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Image Gallery -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Images</h3>
                @if(!empty($product->images) && is_array($product->images))
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($product->images as $image)
                            <img src="{{ $image }}" class="w-full h-48 object-cover rounded-lg border border-gray-200 dark:border-gray-600" alt="{{ $product->name }}">
                        @endforeach
                    </div>
                @else
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12 text-center">
                        <i class="fas fa-image text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">No images uploaded</p>
                    </div>
                @endif
            </div>

            <!-- Description -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Description</h3>
                @if($product->description)
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                        {{ $product->description }}
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 italic">No description provided.</p>
                @endif
            </div>

            <!-- Tags -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tags</h3>
                @if($product->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->tags as $tag)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 italic">No tags assigned.</p>
                @endif
            </div>

            <!-- Analytics Placeholder -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Analytics</h3>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-8 text-center">
                    <i class="fas fa-chart-line text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Product analytics and social commerce performance data will appear here.</p>
                    <a href="{{ route('products.analytics', $product) }}" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        View detailed analytics <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status & Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">Status</span>
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                'discontinued' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$product->status] ?? '' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">Price</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($product->getEffectivePrice(), 2) }}</span>
                    </div>
                    @if($product->sale_price)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">Original</span>
                        <span class="text-gray-400 line-through text-sm">${{ number_format($product->price, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Inventory -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Inventory</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">In Stock</span>
                        <span class="font-semibold {{ $product->isLowStock() ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $product->inventory_count }} units
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">Threshold</span>
                        <span class="text-gray-900 dark:text-white">{{ $product->low_stock_threshold }} units</span>
                    </div>
                    @if($product->isLowStock())
                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="text-sm font-medium">Low Stock Warning</span>
                        </div>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Inventory has dropped to or below threshold.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            @if(!empty($product->metadata) && is_array($product->metadata))
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Additional Data</h3>
                <dl class="space-y-2">
                    @foreach($product->metadata as $key => $value)
                    <div class="flex justify-between items-center text-sm">
                        <dt class="text-gray-500 dark:text-gray-400">{{ ucfirst($key) }}</dt>
                        <dd class="text-gray-900 dark:text-white">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('productDetail', () => ({}));
});
</script>
@endpush
@endsection
