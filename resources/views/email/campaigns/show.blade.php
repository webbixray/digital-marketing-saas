@extends('layouts.unified')
@section('title', "{$campaign->name}")
@section('content')
<div class="space-y-6">
<div class="card">
    <div class="card-header">
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
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 120px">Name</th>
                        <td>{{ $campaign->name }}</td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><span class="badge badge-info">{{ ucfirst($campaign->type) }}</span></td>
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
            <div class="col-md-6">
                <table class="table table-bordered">
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
                    <div class="card-body">
                        {{ $campaign->content }}
                    </div>
                </div>
            </div>
        @endif

        @if($campaign->scheduled_at)
            <div class="mt-4">
                <table class="table table-bordered">
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
        <div class="card-header">
            <span>Recipients ({{ $campaign->recipients->count() }})</span>
        </div>
        <div class="card-body">
            <table class="table table-hover">
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
    <a href="{{ route('email.campaigns.index') }}" class="btn btn-secondary">
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

