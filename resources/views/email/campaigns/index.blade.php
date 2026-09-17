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

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="p-6">
        @if($campaigns->count() > 0)
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Subject</th>
                        <th>Sent</th>
                        <th>Open Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->name }}</td>
                            <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ $campaign->type }}</span></td>
                            <td>
                                <span class="badge badge-{{ $campaign->status === 'sent' ? 'success' : ($campaign->status === 'draft' ? 'secondary' : 'warning') }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($campaign->subject, 40) }}</td>
                            <td>{{ $campaign->sent_count }}</td>
                            <td>
                                @if($campaign->open_rate)
                                    {{ number_format($campaign->open_rate, 1) }}%
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('email.campaigns.show', $campaign) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('email.campaigns.edit', $campaign) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('email.campaigns.destroy', $campaign) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            {{ $campaigns->links() }}
        @else
            <div class="text-center py-4">
                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                <p>No email campaigns yet. Create your first campaign to get started.</p>
                <a href="{{ route('email.campaigns.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create Campaign</a>
            </div>
        @endif
    </div>
</div>
</div>
@endsection

