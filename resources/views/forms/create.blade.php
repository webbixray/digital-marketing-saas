@extends('layouts.unified')
@section('title', 'Create Form')

@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Create Form</h3></div>
    <form action="{{ route('forms.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Fields (JSON)</label><textarea name="fields" class="form-control" rows="6" placeholder='[{"name":"name","type":"text","label":"Full Name","required":true}]' required></textarea></div>
            <div class="form-group"><label>Success Message</label><input type="text" name="success_message" class="form-control" placeholder="Thank you!"></div>
            <div class="form-group"><label>Redirect URL</label><input type="url" name="redirect_url" class="form-control" placeholder="https://..."></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('forms.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

