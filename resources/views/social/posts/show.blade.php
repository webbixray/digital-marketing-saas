@extends('layouts.unified')
@section('title', 'Post #' . $post->id)
@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Post #{{ $post->id }}</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ ucfirst($post->platform) }}</span>
                    <span class="badge badge-{{ $post->status === 'published' ? 'success' : ($post->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($post->status) }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3"><strong>Content:</strong><div class="p-3 bg-light rounded">{{ $post->content }}
                @if($post->hashtags)<div class="mb-3"><strong>Hashtags:</strong> @foreach($post->hashtags as $tag)<span class="badge badge-primary mr-1">{{ $tag }}</span>@endforeach</div>@endif
                @if($post->media)<div class="mb-3"><strong>Media:</strong><pre class="text-sm">{{ json_encode($post->media, JSON_PRETTY_PRINT) }}</pre></div>@endif
                @if($post->quality_score)<div class="mb-3"><strong>Quality Score:</strong><span class="badge badge-{{ $post->quality_score >= 60 ? 'success' : 'warning' }}">{{ $post->quality_score }}/100</span></div>@endif
                @if($post->scheduled_at)<div class="mb-3"><strong>Scheduled:</strong> {{ $post->scheduled_at->format('M d, Y H:i') }}</div>@endif
                @if($post->published_at)<div class="mb-3"><strong>Published:</strong> {{ $post->published_at->format('M d, Y H:i') }}</div>@endif
                @if($post->error_message)<div class="mb-3"><strong>Error:</strong><div class="alert alert-danger">{{ $post->error_message }}@endif
                @if($post->external_post_id)<div class="mb-3"><strong>External ID:</strong> {{ $post->external_post_id }}</div>@endif
            </div>
            <div class="card-footer">
                <a href="{{ route('social.posts.edit', $post) }}" class="btn btn-warning"><i class="fas fa-edit mr-1"></i> Edit</a>
                @if($post->status === 'draft' || $post->status === 'scheduled')
                    <form action="{{ route('social.posts.publish', $post) }}" method="POST" class="d-inline">
                        @csrf<button class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i> Publish Now</button>
                    </form>
                @endif
                @if($post->status === 'failed')
                    <form action="{{ route('social.posts.retry', $post) }}" method="POST" class="d-inline">
                        @csrf<button class="btn btn-warning"><i class="fas fa-redo mr-1"></i> Retry</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Stats</h3></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Views</span><strong>{{ $post->views_count }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Likes</span><strong>{{ $post->likes_count }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Comments</span><strong>{{ $post->comments_count }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Shares</span><strong>{{ $post->shares_count }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Clicks</span><strong>{{ $post->clicks_count }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Engagement Rate</span><strong>{{ $post->engagement_rate ?? 0 }}%</strong></li>
                </ul>
            </div>
        </div>
        @if($post->campaigns->count() > 0)
            <div class="card"><div class="card-header"><h3 class="card-title">Campaigns</h3></div>
                <div class="card-body">
                    @foreach($post->campaigns as $campaign)
                        <a href="{{ route('campaigns.show', $campaign) }}" class="badge badge-info mr-1">{{ $campaign->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
</div>
@endsection

