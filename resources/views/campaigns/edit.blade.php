@extends('layouts.unified')
@section('title', 'Edit Campaign')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Campaign</h3></div>
    <form action="{{ route('campaigns.update', $campaign) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $campaign->name }}" required></div>
            <div class="form-group"><label>Type</label><select name="type" class="form-control">@foreach($types as $key => $label)<option value="{{ $key }}" {{ $campaign->type === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ $campaign->description }}</textarea></div>
            <div class="form-group"><label>Objective</label><input type="text" name="objective" class="form-control" value="{{ $campaign->objective }}"></div>
            <div class="form-group"><label>Target Audience</label><input type="text" name="target_audience" class="form-control" value="{{ $campaign->target_audience }}"></div>
            <div class="form-group"><label>Client</label><select name="client_id" class="form-control"><option value="">None</option>@foreach($clients as $client)<option value="{{ $client->id }}" {{ $campaign->client_id === $client->id ? 'selected' : '' }}>{{ $client->name }}</option>@endforeach</select></div>
            <div class="row"><div class="col-md-6"><div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ $campaign->start_date?->format('Y-m-d') }}">
            <div class="col-md-6"><div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ $campaign->end_date?->format('Y-m-d') }}"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

