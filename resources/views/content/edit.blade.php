@extends('layouts.unified')
@section('title', 'Edit Content Asset')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Content Asset</h3></div>
    <form action="{{ route('content.update', $asset) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $asset->name }}" required></div>
            <div class="form-group"><label>Content</label><textarea name="content" class="form-control" rows="6" required>{{ $asset->content }}</textarea></div>
            <div class="form-group"><label>Media URL</label><input type="url" name="media_url" class="form-control" value="{{ $asset->media_url }}"></div>
            <div class="form-group"><label>Tags (comma-separated)</label><input type="text" name="tags" class="form-control" value="{{ implode(', ', $asset->tags ?? []) }}"></div>
            <div class="form-group"><div class="icheck-primary"><input type="checkbox" name="is_public" id="is_public" value="1" {{ $asset->is_public ? 'checked' : '' }}><label for="is_public">Public</label>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('content.show', $asset) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

