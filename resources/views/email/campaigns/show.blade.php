@extends('layouts.unified')
@section('title', "{$campaign->name}")
@section('content')
<div class="space-y-6">
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="d-flex justify-content-between align-items-center">
            <span>Details</span>
            <div>
                <a href="{{ route('email.campaigns.edit', $campaign) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @if($campaign->isEditable() && $campaign->recipients_count > 0)
                    <form action="{{ route('email.campaigns.send', $campaign) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-send"></i> Send Campaign
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12 md:col-span-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                    <tr>
                        <th style="width: 120px">Name</th>
                        <td>{{ $campaign->name }}</td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($campaign->type) }}</span></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge badge-{{ $campaign->status === 'sent' ? 'success' : ($campaign->status === 'draft' ? 'secondary' : ($campaign->status === 'scheduled' ? 'warning' : 'danger')) }}">
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Subject</th>
                        <td>{{ $campaign->subject }}</td>
                    </tr>
                    <tr>
                        <th>From Name</th>
                        <td>{{ $campaign->from_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>From Email</th>
                        <td>{{ $campaign->from_email ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Reply To</th>
                        <td>{{ $campaign->reply_to ?? '—' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-span-12 md:col-span-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                    <tr>
                        <th style="width: 120px">Sent</th>
                        <td>{{ $campaign->sent_count }}</td>
                    </tr>
                    <tr>
                        <th>Opened</th>
                        <td>{{ $campaign->opened_count }}</td>
                    </tr>
                    <tr>
                        <th>Clicked</th>
                        <td>{{ $campaign->clicked_count }}</td>
                    </tr>
                    <tr>
                        <th>Bounced</th>
                        <td>{{ $campaign->bounced_count }}</td>
                    </tr>
                    <tr>
                        <th>Unsubscribed</th>
                        <td>{{ $campaign->unsubscribed_count }}</td>
                    </tr>
                    <tr>
                        <th>Recipients</th>
                        <td>{{ $campaign->recipients_count }}</td>
                    </tr>
                    <tr>
                        <th>Open Rate</th>
                        <td>
                            @if($campaign->open_rate !== null)
                                {{ number_format($campaign->open_rate, 1) }}%
                            @else
                                &mdash;
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Click Rate</th>
                        <td>
                            @if($campaign->click_rate !== null)
                                {{ number_format($campaign->click_rate, 1) }}%
                            @else
                                &mdash;
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($campaign->content)
            <div class="mt-4">
                <h5>Content</h5>
                <div class="card bg-light">
                    <div class="p-6">
                        {{ $campaign->content }}
                    </div>
                </div>
            </div>
        @endif

        @if($campaign->scheduled_at)
            <div class="mt-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                    <tr>
                        <th style="width: 120px">Scheduled At</th>
                        <td>{{ $campaign->scheduled_at->format('Y-m-d H:i') }}</td>
                    </tr>
                </table>
            </div>
        @endif
    </div>
</div>

@if($campaign->recipients->count() > 0)
    <div class="card mt-3">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <span>Recipients ({{ $campaign->recipients->count() }})</span>
        </div>
        <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaign->recipients as $recipient)
                        <tr>
                            <td>{{ $recipient->email }}</td>
                            <td>{{ $recipient->name ?? '—' }}</td>
                            <td>
                                <span class="badge badge-{{ $recipient->status === 'sent' ? 'success' : ($recipient->status === 'pending' ? 'secondary' : 'danger') }}">
                                    {{ ucfirst($recipient->status) }}
                                </span>
                            </td>
                            <td>{{ $recipient->sent_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="mt-3">
    <a href="{{ route('email.campaigns.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
        <i class="fas fa-arrow-left"></i> Back to Campaigns
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif
</div>
@endsection

