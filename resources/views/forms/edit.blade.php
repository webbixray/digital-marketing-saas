@extends('layouts.unified')
@section('title', 'Edit Form')

@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Form</h3></div>
    <form action="{{ route('forms.update', $form) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $form->name }}" required></div>
            <div class="form-group"><label>Fields (JSON)</label><textarea name="fields" class="form-control" rows="6" required>{{ json_encode($form->fields, JSON_PRETTY_PRINT) }}</textarea></div>
            <div class="form-group"><label>Success Message</label><input type="text" name="success_message" class="form-control" value="{{ $form->success_message }}"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('forms.show', $form) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

