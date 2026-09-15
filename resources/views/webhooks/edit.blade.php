@extends('layouts.unified')
@section('title', 'Edit Webhook')

@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Webhook</h3></div>
    <form action="{{ route('webhooks.update', $webhook) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $webhook->name }}" required></div>
            <div class="form-group"><label>URL</label><input type="url" name="url" class="form-control" value="{{ $webhook->url }}" required></div>
            <div class="form-group"><label>Events</label>
                @foreach($events as $key => $label)
                    <div class="icheck-primary">
                        <input type="checkbox" name="events[]" id="event_{{ $key }}" value="{{ $key }}" {{ in_array($key, $webhook->events ?? []) ? 'checked' : '' }}>
                        <label for="event_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            <div class="form-group"><div class="icheck-primary"><input type="checkbox" name="is_active" id="is_active" value="1" {{ $webhook->is_active ? 'checked' : '' }}><label for="is_active">Active</label>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('webhooks.show', $webhook) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

