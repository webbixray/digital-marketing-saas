@extends('layouts.unified')
@section('title', $page->name)
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">{{ $page->name }}</h3></div>
                <div class="p-6">
                    <div class="mb-3 p-4 rounded" style="background: {{ $page->background_color }}; color: {{ $page->text_color }};">
                        <h2>{{ $page->headline }}</h2>
                        <div>{{ $page->content }}</div>
                        @if($page->cta_text && $page->cta_url)
                            <a href="{{ $page->cta_url }}" class="px-4 py-2 rounded-lg mt-3" style="background: {{ $page->button_color }}; color: {{ $page->button_text_color }};">{{ $page->cta_text }}</a>
                        @endif
                    </div>
                    <hr class="my-4 border-gray-200 dark:border-gray-700">
                    <p><strong>Public URL:</strong> <a href="{{ route('public.landing-page', $page->slug) }}" target="_blank">{{ route('public.landing-page', $page->slug) }}</a></p>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><a href="{{ route('landing-pages.edit', $page) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-edit mr-1"></i> Edit</a></div>
            </div>
        </div>
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Stats</h3></div>
                <div class="p-6 space-y-2">
                    <p><strong>Views:</strong> {{ $page->views_count }}</p>
                    <p><strong>Clicks:</strong> {{ $page->clicks_count }}</p>
                    <p><strong>Conversions:</strong> {{ $page->conversions_count }}</p>
                    <p><strong>Conv. Rate:</strong> {{ $page->conversion_rate ?? 0 }}%</p>
                    <p><strong>Status:</strong> <span class="px-2 py-1 text-xs font-medium rounded-full {{ $page->is_published ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
