@extends('layouts.unified')
@section('title', 'Email Campaigns')
@section('content')
<div class="space-y-6">
<div class="flex items-center justify-between mb-4">
    <a href="{{ route('email.campaigns.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create Campaign
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if($campaigns->count() > 0)
            <table class="table table-hover">
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
                            <td><span class="badge badge-info">{{ $campaign->type }}</span></td>
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
            </table>
            {{ $campaigns->links() }}
        @else
            <div class="text-center py-4">
                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                <p>No email campaigns yet. Create your first campaign to get started.</p>
                <a href="{{ route('email.campaigns.create') }}" class="btn btn-primary">Create Campaign</a>
            </div>
        @endif
    </div>
</div>
</div>
@endsection

