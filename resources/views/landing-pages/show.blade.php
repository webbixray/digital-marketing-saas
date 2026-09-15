@extends('layouts.unified')
@section('title', $page->name)
@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $page->name }}</h3></div>
            <div class="card-body">
                <div class="mb-3 p-4 rounded" style="background: {{ $page->background_color }}; color: {{ $page->text_color }};">
                    <h2>{{ $page->headline }}</h2>
                    <div>{{ $page->content }}</div>
                    @if($page->cta_text && $page->cta_url)
                        <a href="{{ $page->cta_url }}" class="btn mt-3" style="background: {{ $page->button_color }}; color: {{ $page->button_text_color }};">{{ $page->cta_text }}</a>
                    @endif
                </div>
                <hr>
                <p><strong>Public URL:</strong> <a href="{{ route('public.landing-page', $page->slug) }}" target="_blank">{{ route('public.landing-page', $page->slug) }}</a></p>
            </div>
            <div class="card-footer"><a href="{{ route('landing-pages.edit', $page) }}" class="btn btn-warning"><i class="fas fa-edit mr-1"></i> Edit</a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Stats</h3></div>
            <div class="card-body">
                <strong>Views:</strong> {{ $page->views_count }}<hr>
                <strong>Clicks:</strong> {{ $page->clicks_count }}<hr>
                <strong>Conversions:</strong> {{ $page->conversions_count }}<hr>
                <strong>Conv. Rate:</strong> {{ $page->conversion_rate ?? 0 }}%<hr>
                <strong>Status:</strong> <span class="badge badge-{{ $page->is_published ? 'success' : 'secondary' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

