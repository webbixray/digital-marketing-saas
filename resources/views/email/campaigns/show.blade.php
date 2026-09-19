@extends('layouts.unified')
@section('title', "{$campaign->name}")
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <span class="font-semibold text-gray-900 dark:text-white">Details</span>
            <div class="flex gap-2">
                <a href="{{ route('email.campaigns.edit', $campaign) }}" class="bg-yellow-500 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-yellow-600 inline-flex items-center gap-1 font-medium transition-colors">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @if($campaign->isEditable() && $campaign->recipients_count > 0)
                    <form action="{{ route('email.campaigns.send', $campaign) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-green-700 inline-flex items-center gap-1 font-medium transition-colors">
                            <i class="fas fa-paper-plane"></i> Send Campaign
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-12 md:col-span-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Name</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->name }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Type</th><td class="px-4 py-2"><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($campaign->type) }}</span></td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Status</th><td class="px-4 py-2">
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $campaign->status === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($campaign->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300' : ($campaign->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300')) }}">{{ ucfirst($campaign->status) }}</span>
                        </td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Subject</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->subject }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">From Name</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->from_name ?? '—' }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">From Email</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->from_email ?? '—' }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Reply To</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->reply_to ?? '—' }}</td></tr>
                    </table></div>
                </div>
                <div class="col-span-12 md:col-span-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Sent</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->sent_count }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Opened</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->opened_count }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Clicked</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->clicked_count }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Bounced</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->bounced_count }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Unsubscribed</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->unsubscribed_count }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Recipients</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->recipients_count }}</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Open Rate</th><td class="px-4 py-2 text-gray-900 dark:text-white">@if($campaign->open_rate !== null){{ number_format($campaign->open_rate, 1) }}%@else &mdash; @endif</td></tr>
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Click Rate</th><td class="px-4 py-2 text-gray-900 dark:text-white">@if($campaign->click_rate !== null){{ number_format($campaign->click_rate, 1) }}%@else &mdash; @endif</td></tr>
                    </table></div>
                </div>
            </div>

            @if($campaign->content)
                <div class="mt-4">
                    <h5 class="font-semibold text-gray-900 dark:text-white mb-2">Content</h5>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <pre class="whitespace-pre-wrap text-sm text-gray-900 dark:text-white">{{ $campaign->content }}</pre>
                    </div>
                </div>
            @endif

            @if($campaign->scheduled_at)
                <div class="mt-4">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                        <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Scheduled At</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $campaign->scheduled_at->format('Y-m-d H:i') }}</td></tr>
                    </table></div>
                </div>
            @endif
        </div>
    </div>

    @if($campaign->recipients->count() > 0)
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <span class="font-semibold text-gray-900 dark:text-white">Recipients ({{ $campaign->recipients->count() }})</span>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaign->recipients as $recipient)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $recipient->email }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $recipient->name ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $recipient->status === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($recipient->status === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">{{ ucfirst($recipient->status) }}</span>
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $recipient->sent_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        </div>
    @endif

    <div>
        <a href="{{ route('email.campaigns.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-arrow-left"></i> Back to Campaigns
        </a>
    </div>
</div>
