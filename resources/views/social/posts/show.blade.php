@extends('layouts.unified')
@section('title', 'Post #' . $post->id)
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Post #{{ $post->id }}</h3>
                    <div class="ml-auto">
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($post->platform) }}</span>
                        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $post->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($post->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300') }}">{{ ucfirst($post->status) }}</span>
                    </div>
                </div>
                <div class="p-6 space-y-3">
                    <div><strong>Content:</strong><div class="w-full p-3 bg-gray-100 dark:bg-gray-700 rounded-lg break-words mt-1">{{ $post->content }}</div></div>
                    @if($post->hashtags)
                    <div><strong>Hashtags:</strong> @foreach($post->hashtags as $tag)<span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300 mr-1">{{ $tag }}</span>@endforeach</div>
                    @endif
                    @if($post->media)
                    <div><strong>Media:</strong><pre class="text-sm mt-1 bg-gray-50 dark:bg-gray-700 rounded p-3 overflow-x-auto">{{ json_encode($post->media, JSON_PRETTY_PRINT) }}</pre></div>
                    @endif
                    @if($post->quality_score)
                    <div><strong>Quality Score:</strong><span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $post->quality_score >= 60 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }}">{{ $post->quality_score }}/100</span></div>
                    @endif
                    @if($post->scheduled_at)
                    <div><strong>Scheduled:</strong> {{ $post->scheduled_at->format('M d, Y H:i') }}</div>
                    @endif
                    @if($post->published_at)
                    <div><strong>Published:</strong> {{ $post->published_at->format('M d, Y H:i') }}</div>
                    @endif
                    @if($post->error_message)
                    <div><strong>Error:</strong><div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4 mt-1">{{ $post->error_message }}</div></div>
                    @endif
                    @if($post->external_post_id)
                    <div><strong>External ID:</strong> {{ $post->external_post_id }}</div>
                    @endif
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <a href="{{ route('social.posts.edit', $post) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-edit mr-1"></i> Edit</a>
                    @if($post->status === 'draft' || $post->status === 'scheduled')
                    <form action="{{ route('social.posts.publish', $post) }}" method="POST" class="inline-block">
                        @csrf<button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-paper-plane mr-1"></i> Publish Now</button>
                    </form>
                    @endif
                    @if($post->status === 'failed')
                    <form action="{{ route('social.posts.retry', $post) }}" method="POST" class="inline-block">
                        @csrf<button class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-redo mr-1"></i> Retry</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Stats</h3></div>
                <div class="p-6">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li class="flex justify-between items-center py-3"><span>Views</span><strong>{{ $post->views_count }}</strong></li>
                        <li class="flex justify-between items-center py-3"><span>Likes</span><strong>{{ $post->likes_count }}</strong></li>
                        <li class="flex justify-between items-center py-3"><span>Comments</span><strong>{{ $post->comments_count }}</strong></li>
                        <li class="flex justify-between items-center py-3"><span>Shares</span><strong>{{ $post->shares_count }}</strong></li>
                        <li class="flex justify-between items-center py-3"><span>Clicks</span><strong>{{ $post->clicks_count }}</strong></li>
                        <li class="flex justify-between items-center py-3"><span>Engagement Rate</span><strong>{{ $post->engagement_rate ?? 0 }}%</strong></li>
                    </ul>
                </div>
            </div>
            @if($post->campaigns->count() > 0)
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Campaigns</h3></div>
                <div class="p-6">
                    @foreach($post->campaigns as $campaign)
                    <a href="{{ route('campaigns.show', $campaign) }}" class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300 mr-1">{{ $campaign->name }}</a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
