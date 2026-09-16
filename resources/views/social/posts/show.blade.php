@extends('layouts.unified')
@section('title', 'Post #' . $post->id)
@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Post #{{ $post->id }}</h3>
                <div class="card-tools">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($post->platform) }}</span>
                    <span class="badge badge-{{ $post->status === 'published' ? 'success' : ($post->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($post->status) }}</span>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-3"><strong>Content:</strong><div class="p-3 bg-light rounded">{{ $post->content }}
                @if($post->hashtags)<div class="mb-3"><strong>Hashtags:</strong> @foreach($post->hashtags as $tag)<span class="badge badge-primary mr-1">{{ $tag }}</span>@endforeach</div>@endif
                @if($post->media)<div class="mb-3"><strong>Media:</strong><pre class="text-sm">{{ json_encode($post->media, JSON_PRETTY_PRINT) }}</pre></div>@endif
                @if($post->quality_score)<div class="mb-3"><strong>Quality Score:</strong><span class="badge badge-{{ $post->quality_score >= 60 ? 'success' : 'warning' }}">{{ $post->quality_score }}/100</span></div>@endif
                @if($post->scheduled_at)<div class="mb-3"><strong>Scheduled:</strong> {{ $post->scheduled_at->format('M d, Y H:i') }}</div>@endif
                @if($post->published_at)<div class="mb-3"><strong>Published:</strong> {{ $post->published_at->format('M d, Y H:i') }}</div>@endif
                @if($post->error_message)<div class="mb-3"><strong>Error:</strong><div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4 mb-4">{{ $post->error_message }}@endif
                @if($post->external_post_id)<div class="mb-3"><strong>External ID:</strong> {{ $post->external_post_id }}</div>@endif
            </div>
            <div class="card-footer">
                <a href="{{ route('social.posts.edit', $post) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-edit mr-1"></i> Edit</a>
                @if($post->status === 'draft' || $post->status === 'scheduled')
                    <form action="{{ route('social.posts.publish', $post) }}" method="POST" class="d-inline">
                        @csrf<button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-paper-plane mr-1"></i> Publish Now</button>
                    </form>
                @endif
                @if($post->status === 'failed')
                    <form action="{{ route('social.posts.retry', $post) }}" method="POST" class="d-inline">
                        @csrf<button class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-redo mr-1"></i> Retry</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Stats</h3></div>
            <div class="p-6">
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
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Campaigns</h3></div>
                <div class="p-6">
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

