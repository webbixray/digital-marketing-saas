@extends('layouts.unified')

@section('title', 'Commerce Post Details')

@section('content')
<div x-data="commerceDetail">
    <x-flash-messages />

    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('social-commerce.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Commerce Post</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    @if($post->product){{ $post->product->name }} — @endif
                    {{ $post->created_at->format('M d, Y') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Clicks</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($post->clicks) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Conversions</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($post->conversions) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CTR</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $post->getCtrAttribute() }}%</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">${{ number_format($post->revenue, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Linked Product -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Linked Product</h3>
            @if($post->product)
            <div class="flex items-center gap-4">
                @if(!empty($post->product->images) && is_array($post->product->images))
                    <img src="{{ $post->product->images[0] }}" class="w-16 h-16 rounded-lg object-cover" alt="">
                @else
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-gray-400 text-xl"></i>
                    </div>
                @endif
                <div>
                    <a href="{{ route('products.show', $post->product) }}" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600">{{ $post->product->name }}</a>
                    <p class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ $post->product->sku ?? 'N/A' }} — ${{ number_format($post->product->getEffectivePrice(), 2) }}</p>
                </div>
            </div>
            @else
            <p class="text-gray-500 dark:text-gray-400 italic">Product no longer available.</p>
            @endif
        </div>

        <!-- Linked Social Post -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Social Post</h3>
            @if($post->socialPost)
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">{{ ucfirst($post->socialPost->platform) }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $post->socialPost->published_at?->format('M d, Y') }}</span>
                </div>
                <p class="text-gray-700 dark:text-gray-300 text-sm">{{ Str::limit($post->socialPost->content, 200) }}</p>
            </div>
            @else
            <p class="text-gray-500 dark:text-gray-400 italic">Social post no longer available.</p>
            @endif
        </div>

        <!-- Commerce Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Commerce Details</h3>
            <dl class="space-y-3">
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Shop URL</dt>
                    <dd class="text-sm">
                        @if($post->shop_url)
                            <a href="{{ $post->shop_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 truncate max-w-[250px]">{{ $post->shop_url }}</a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Discount Code</dt>
                    <dd class="text-sm font-mono">
                        @if($post->discount_code)
                            <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ $post->discount_code }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Average Order Value</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">${{ number_format($post->getAverageOrderValueAttribute(), 2) }}</dd>
                </div>
            </dl>
        </div>

        <!-- UTM Parameters -->
        @if(!empty($post->utm_params) && is_array($post->utm_params))
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">UTM Parameters</h3>
            <dl class="space-y-3">
                @foreach($post->utm_params as $key => $value)
                <div class="flex justify-between items-center">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                    <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('commerceDetail', () => ({}));
});
</script>
@endpush
@endsection
