@extends('layouts.unified')
@section('title', 'Activity Details')

@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Activity Details</h3></div>
            <div class="card-body">
                <strong>Action:</strong> <span class="badge badge-info">{{ $log->action }}</span><hr>
                <strong>Description:</strong> {{ $log->description }}<hr>
                <strong>User:</strong> {{ $log->user?->name ?? 'System' }}<hr>
                <strong>Date:</strong> {{ $log->created_at->format('M d, Y H:i:s') }}<hr>
                @if($log->metadata)
                    <strong>Metadata:</strong>
                    <pre class="text-sm">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

