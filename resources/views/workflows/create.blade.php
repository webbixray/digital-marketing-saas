@extends('layouts.unified')
@section('title', 'Create Workflow')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-10"><div class="card"><div class="card-header"><h3 class="card-title">Create Workflow</h3></div>
    <form action="{{ route('workflows.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Trigger</label>
                <select name="trigger_type" class="form-control" id="triggerSelect">
                    @foreach($triggerTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Actions (JSON)</label>
                <textarea name="actions" class="form-control" rows="6" placeholder='[{"type":"send_notification","config":{"message":"New post published"}}]'>{{ old('actions') }}</textarea>
                <small class="text-muted">Available actions: {{ implode(', ', array_keys($actionTypes)) }}</small>
            </div>
            <div class="form-group"><label>Conditions (JSON, optional)</label>
                <textarea name="conditions" class="form-control" rows="3" placeholder='{"platform":"facebook"}'>{{ old('conditions') }}</textarea>
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('workflows.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

