@extends('layouts.unified')
@section('title', $asset->name)
@section('content')
<div class="space-y-6">
<div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">{{ $asset->name }}</h3></div>
    <div class="card-body">
        <div class="mb-3">{{ $asset->content }}</div>
        @if($asset->media_url)<div class="mb-3"><img src="{{ $asset->media_url }}" class="img-fluid rounded" alt=""></div>@endif
        <hr>
        <p><strong>Type:</strong> {{ \App\Models\ContentAsset::ASSET_TYPES[$asset->type] ?? $asset->type }}</p>
        <p><strong>Tags:</strong> @foreach($asset->tags ?? [] as $tag)<span class="badge badge-secondary mr-1">{{ $tag }}</span>@endforeach</p>
        <p><strong>Usage Count:</strong> {{ $asset->usage_count }}</p>
    </div>
    <div class="card-footer"><a href="{{ route('content.edit', $asset) }}" class="btn btn-warning"><i class="fas fa-edit mr-1"></i> Edit</a></div>
</div></div></div>
</div>
@endsection
