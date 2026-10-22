@extends('layouts.unified')
@section('title', $campaign->name)
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-12 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Campaign Details</h3></div>
                <div class="p-6 space-y-3">
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-bullhorn mr-1"></i> Name</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $campaign->name }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-tag mr-1"></i> Type</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ \App\Models\Campaign::CAMPAIGN_TYPES[$campaign->type] ?? $campaign->type }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-info-circle mr-1"></i> Status</strong>
                        <span class="inline-block mt-1 px-2 py-1 text-xs font-medium rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($campaign->status) }}</span>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-user mr-1"></i> Client</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $campaign->client?->name ?? 'None' }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-calendar mr-1"></i> Period</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $campaign->start_date?->format('M d, Y') }} — {{ $campaign->end_date?->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <strong class="text-gray-700 dark:text-gray-300"><i class="fas fa-align-left mr-1"></i> Description</strong>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $campaign->description ?? 'No description' }}</p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <form action="{{ route('campaigns.status', $campaign) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $campaign->status === 'active' ? 'paused' : 'active' }}">
                        <button class="px-3 py-1.5 text-sm rounded-lg {{ $campaign->status === 'active' ? 'bg-yellow-500 text-white hover:bg-yellow-600' : 'bg-green-600 text-white hover:bg-green-700' }} font-medium transition-colors">{{ $campaign->status === 'active' ? 'Pause' : 'Activate' }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Posts</h3></div>
                <div class="p-0">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60"><tr><th>Platform</th><th>Content</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($posts as $post)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($post->platform) }}</span></td>
                                    <td>{{ Str::limit($post->content, 50) }}</td>
                                    <td><span class="px-2 py-1 text-xs font-medium rounded-full {{ $post->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }}">{{ ucfirst($post->status) }}</span></td>
                                    <td>{{ $post->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-6 text-gray-500 dark:text-gray-400">No posts in this campaign</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection