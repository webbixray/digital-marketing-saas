@extends('layouts.unified')
@section('title', $asset->name)
@section('content')
<div class="space-y-6">
<div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">{{ $asset->name }}</h3></div>
    <div class="p-6">
        <div class="mb-3">{{ $asset->content }}</div>
        @if($asset->media_url)<div class="mb-3"><img src="{{ $asset->media_url }}" class="img-fluid rounded" alt=""></div>@endif
        <hr>
        <p><strong>Type:</strong> {{ \App\Models\ContentAsset::ASSET_TYPES[$asset->type] ?? $asset->type }}</p>
        <p><strong>Tags:</strong> @foreach($asset->tags ?? [] as $tag)<span class="badge badge-secondary mr-1">{{ $tag }}</span>@endforeach</p>
        <p><strong>Usage Count:</strong> {{ $asset->usage_count }}</p>
    </div>
    <div class="card-footer"><a href="{{ route('content.edit', $asset) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-edit mr-1"></i> Edit</a></div>
</div></div></div>
</div>
@endsection
