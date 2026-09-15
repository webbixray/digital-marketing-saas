@extends('layouts.unified')
@section('title', 'Create Webhook')

@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Create Webhook</h3></div>
    <form action="{{ route('webhooks.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required placeholder="My Webhook"></div>
            <div class="form-group"><label>URL</label><input type="url" name="url" class="form-control" required placeholder="https://example.com/webhook"></div>
            <div class="form-group"><label>Events</label>
                @foreach($events as $key => $label)
                    <div class="icheck-primary">
                        <input type="checkbox" name="events[]" id="event_{{ $key }}" value="{{ $key }}">
                        <label for="event_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            <div class="form-group"><div class="icheck-primary"><input type="checkbox" name="is_active" id="is_active" value="1" checked><label for="is_active">Active</label>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('webhooks.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

