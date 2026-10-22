@extends('layouts.unified')
@section('title', $client->name)
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-12 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Client Details</h3></div>
                <div class="p-6 space-y-3">
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-user mr-1"></i> Name</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $client->name }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-envelope mr-1"></i> Email</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $client->email }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-phone mr-1"></i> Phone</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $client->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-building mr-1"></i> Company</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $client->company ?? '—' }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-industry mr-1"></i> Industry</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $client->industry ?? '—' }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-info-circle mr-1"></i> Status</strong>
                        <span class="inline-block mt-1 px-2 py-1 text-xs font-medium rounded-full {{ $client->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($client->status) }}</span>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-sticky-note mr-1"></i> Notes</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $client->notes ?? 'No notes' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Campaigns</h3></div>
                <div class="p-0">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60"><tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"><th>Name</th><th>Type</th><th>Status</th><th>Posts</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($campaigns as $campaign)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td><a href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                                    <td>{{ \App\Models\Campaign::CAMPAIGN_TYPES[$campaign->type] ?? $campaign->type }}</td>
                                    <td><span class="px-2 py-1 text-xs font-medium rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($campaign->status) }}</span></td>
                                    <td>{{ $campaign->posts_count }}</td>
                                </tr>
                            @empty
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"><td colspan="4" class="text-center py-6 text-gray-500 dark:text-gray-400">No campaigns</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection