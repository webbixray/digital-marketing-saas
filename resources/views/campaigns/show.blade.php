@extends('layouts.unified')
@section('title', $campaign->name)
@section('content')
<div class="space-y-6">
<div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Campaign Details</h3></div>
            <div class="card-body">
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
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Posts</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Platform</th><th>Content</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td><span class="badge badge-info">{{ ucfirst($post->platform) }}</span></td>
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
