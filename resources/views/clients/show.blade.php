@extends('layouts.unified')
@section('title', $client->name)
@section('content')
<div class="space-y-6">
<div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Client Details</h3></div>
            <div class="p-6">
                <strong><i class="fas fa-user mr-1"></i> Name</strong><p class="text-muted">{{ $client->name }}</p><hr>
                <strong><i class="fas fa-envelope mr-1"></i> Email</strong><p class="text-muted">{{ $client->email }}</p><hr>
                <strong><i class="fas fa-phone mr-1"></i> Phone</strong><p class="text-muted">{{ $client->phone ?? '—' }}</p><hr>
                <strong><i class="fas fa-building mr-1"></i> Company</strong><p class="text-muted">{{ $client->company ?? '—' }}</p><hr>
                <strong><i class="fas fa-industry mr-1"></i> Industry</strong><p class="text-muted">{{ $client->industry ?? '—' }}</p><hr>
                <strong><i class="fas fa-info-circle mr-1"></i> Status</strong><span class="badge badge-{{ $client->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($client->status) }}</span><hr>
                <strong><i class="fas fa-sticky-note mr-1"></i> Notes</strong><p class="text-muted">{{ $client->notes ?? 'No notes' }}</p>
            </div>
        </div>
    </div>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Campaigns</h3></div>
            <div class="card-body p-0">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Posts</th></tr></thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                            <tr>
                                <td><a href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                                <td>{{ \App\Models\Campaign::CAMPAIGN_TYPES[$campaign->type] ?? $campaign->type }}</td>
                                <td><span class="badge badge-{{ $campaign->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($campaign->status) }}</span></td>
                                <td>{{ $campaign->posts_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No campaigns</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
