@extends('layouts.unified')
@section('title', 'Create Campaign')
@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Create Campaign</h3></div>
            <form action="{{ route('campaigns.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Type</label>
                        <select name="type" class="form-control">
                            @foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="form-group"><label>Objective</label><input type="text" name="objective" class="form-control"></div>
                    <div class="form-group"><label>Target Audience</label><input type="text" name="target_audience" class="form-control"></div>
                    <div class="form-group"><label>Client</label>
                        <select name="client_id" class="form-control"><option value="">None</option>
                            @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control">
                        <div class="col-md-6"><div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control">
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('campaigns.index') }}" class="btn btn-default">Cancel</a></div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

