@extends('layouts.unified')
@section('title', $campaign->name)
@section('content')
<div class="space-y-6">
<div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Campaign Details</h3></div>
            <div class="p-6">
                <strong><i class="fas fa-bullhorn mr-1"></i> Name</strong><p class="text-muted">{{ $campaign->name }}</p><hr>
                <strong><i class="fas fa-tag mr-1"></i> Type</strong><p class="text-muted">{{ \App\Models\Campaign::CAMPAIGN_TYPES[$campaign->type] ?? $campaign->type }}</p><hr>
                <strong><i class="fas fa-info-circle mr-1"></i> Status</strong><span class="badge badge-{{ $campaign->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($campaign->status) }}</span><hr>
                <strong><i class="fas fa-user mr-1"></i> Client</strong><p class="text-muted">{{ $campaign->client?->name ?? 'None' }}</p><hr>
                <strong><i class="fas fa-calendar mr-1"></i> Period</strong><p class="text-muted">{{ $campaign->start_date?->format('M d, Y') }} — {{ $campaign->end_date?->format('M d, Y') }}</p><hr>
                <strong><i class="fas fa-align-left mr-1"></i> Description</strong><p class="text-muted">{{ $campaign->description ?? 'No description' }}</p>
            </div>
            <div class="card-footer">
                <form action="{{ route('campaigns.status', $campaign) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="status" value="{{ $campaign->status === 'active' ? 'paused' : 'active' }}">
                    <button class="btn btn-sm btn-{{ $campaign->status === 'active' ? 'warning' : 'success' }}">{{ $campaign->status === 'active' ? 'Pause' : 'Activate' }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Posts</h3></div>
            <div class="card-body p-0">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead><tr><th>Platform</th><th>Content</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($post->platform) }}</span></td>
                                <td>{{ Str::limit($post->content, 50) }}</td>
                                <td><span class="badge badge-{{ $post->status === 'published' ? 'success' : 'warning' }}">{{ ucfirst($post->status) }}</span></td>
                                <td>{{ $post->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No posts in this campaign</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
