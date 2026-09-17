@extends('layouts.unified')
@section('title', $asset->name)
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">{{ $asset->name }}</h3></div>
                <div class="p-6 space-y-4">
                    <div class="text-gray-700 dark:text-gray-300">{{ $asset->content }}</div>
                    @if($asset->media_url)<div><img loading="lazy" src="{{ $asset->media_url }}" class="max-w-full rounded-lg" alt=""></div>@endif
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                        <p class="text-gray-700 dark:text-gray-300"><strong>Type:</strong> {{ \App\Models\ContentAsset::ASSET_TYPES[$asset->type] ?? $asset->type }}</p>
                        <p class="text-gray-700 dark:text-gray-300"><strong>Tags:</strong> @foreach($asset->tags ?? [] as $tag)<span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 mr-1">{{ $tag }}</span>@endforeach</p>
                        <p class="text-gray-700 dark:text-gray-300"><strong>Usage Count:</strong> {{ $asset->usage_count }}</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('content.edit', $asset) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-edit mr-1"></i> Edit</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection