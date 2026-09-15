@extends('layouts.unified')
@section('title', 'Edit Post')
@section('content')
<div class="space-y-6">
<div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Post #{{ $post->id }}</h3></div>
    <form action="{{ route('social.posts.update', $post) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Social Account</label><select name="social_account_id" class="form-control">@foreach($accounts as $account)<option value="{{ $account->id }}" {{ $post->social_account_id === $account->id ? 'selected' : '' }}>{{ ucfirst($account->platform) }} - {{ $account->platform_display_name ?? 'Account' }}</option>@endforeach</select></div>
            <div class="form-group"><label>Content</label><textarea name="content" rows="5" class="form-control" required>{{ $post->content }}</textarea></div>
            <div class="form-group"><label>Hashtags (comma-separated)</label><input type="text" name="hashtags[]" class="form-control" value="{{ implode(',', $post->hashtags ?? []) }}"></div>
            <div class="form-group"><label>Schedule</label><input type="datetime-local" name="scheduled_at" class="form-control" value="{{ $post->scheduled_at?->format('Y-m-d\TH:i') }}"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('social.posts.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div></div></div>
</div>
@endsection
