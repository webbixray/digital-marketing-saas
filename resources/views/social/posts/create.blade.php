@extends('layouts.unified')
@section('title', 'Create Post')

@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create New Post</h3>
            </div>
            <form action="{{ route('social.posts.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Social Account</label>
                        <select name="social_account_id" class="form-control @error('social_account_id') is-invalid @enderror" required>
                            <option value="">Select an account...</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('social_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ ucfirst($account->platform) }} - {{ $account->platform_display_name ?? 'Account' }}
                                </option>
                            @endforeach
                        </select>
                        @error('social_account_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" rows="5" class="form-control @error('content') is-invalid @enderror" required placeholder="Write your post content...">{{ old('content') }}</textarea>
                        @error('content') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Hashtags (comma-separated)</label>
                        <input type="text" name="hashtags[]" class="form-control" placeholder="#marketing,#socialmedia" value="{{ old('hashtags') }}">
                    </div>
                    <div class="form-group">
                        <label>Schedule (optional)</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}">
                        <small class="text-muted">Leave empty to save as draft</small>
                    </div>
                    @if($campaigns->count() > 0)
                        <div class="form-group">
                            <label>Attach to Campaign</label>
                            <select name="campaign_id" class="form-control">
                                <option value="">None</option>
                                @foreach($campaigns as $campaign)
                                    <option value="{{ $campaign->id }}" {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>{{ $campaign->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Post</button>
                    <a href="{{ route('social.posts.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Tips</h3></div>
            <div class="card-body">
                <ul class="text-sm text-muted">
                    <li>Use relevant hashtags for visibility</li>
                    <li>Best posting times: 9AM, 12PM, 6PM</li>
                    <li>Include a clear call-to-action</li>
                    <li>Add media for higher engagement</li>
                    <li>Character limit varies by platform</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

