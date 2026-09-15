@extends('layouts.unified')
@section('title', 'Create Content Asset')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Create Content Asset</h3></div>
    <form action="{{ route('content.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Type</label>
                <select name="type" class="form-control">@foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
            </div>
            <div class="form-group"><label>Content</label><textarea name="content" class="form-control" rows="6" required></textarea></div>
            <div class="form-group"><label>Media URL</label><input type="url" name="media_url" class="form-control"></div>
            <div class="form-group"><label>Tags (comma-separated)</label><input type="text" name="tags" class="form-control" placeholder="marketing, social"></div>
            <div class="form-group"><div class="icheck-primary"><input type="checkbox" name="is_public" id="is_public" value="1"><label for="is_public">Public</label>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('content.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

