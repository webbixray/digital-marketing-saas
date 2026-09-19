@extends('layouts.unified')
@section('title', 'Email Campaigns')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('email.campaigns.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-plus"></i> Create Campaign
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            @if($campaigns->count() > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Open Rate</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($campaigns as $campaign)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $campaign->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $campaign->type }}</span></td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $campaign->status === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($campaign->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300') }}">{{ ucfirst($campaign->status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($campaign->subject, 40) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->sent_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($campaign->open_rate)
                                        {{ number_format($campaign->open_rate, 1) }}%
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('email.campaigns.show', $campaign) }}" class="px-3 py-1 text-sm border border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 font-medium transition-colors">View</a>
                                    <a href="{{ route('email.campaigns.edit', $campaign) }}" class="px-3 py-1 text-sm border border-yellow-300 dark:border-yellow-600 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20 font-medium transition-colors">Edit</a>
                                    <form action="{{ route('email.campaigns.destroy', $campaign) }}" method="POST" class="inline" onsubmit="return confirm('Delete this campaign?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-sm border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
                <div class="mt-4">
                    {{ $campaigns->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-envelope-open-text fa-3x text-gray-400 mb-3"></i>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">No email campaigns yet. Create your first campaign to get started.</p>
                    <a href="{{ route('email.campaigns.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create Campaign</a>
                </div>
            @endif
        </div>
    </div>
</div>
