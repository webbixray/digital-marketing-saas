@extends('layouts.unified')
@section('title', 'Edit Workflow')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-10"><div class="card"><div class="card-header"><h3 class="card-title">Edit Workflow</h3></div>
    <form action="{{ route('workflows.update', $workflow) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $workflow->name }}" required></div>
            <div class="form-group"><label>Trigger</label>
                <select name="trigger_type" class="form-control">@foreach($triggerTypes as $key => $label)<option value="{{ $key }}" {{ $workflow->trigger_type === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="form-group"><label>Actions (JSON)</label><textarea name="actions" class="form-control" rows="6">{{ json_encode($workflow->actions, JSON_PRETTY_PRINT) }}</textarea></div>
            <div class="form-group"><label>Conditions (JSON, optional)</label><textarea name="conditions" class="form-control" rows="3">{{ json_encode($workflow->conditions, JSON_PRETTY_PRINT) }}</textarea></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

