@extends('layouts.unified')
@section('title', 'Connect Account')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Connect Social Account</h3></div>
    <form action="{{ route('social.accounts.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Platform</label>
                <select name="platform" class="form-control" required>
                    <option value="">Select platform...</option>
                    @foreach($platforms as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Access Token</label><input type="text" name="access_token" class="form-control" required placeholder="Enter platform access token"></div>
            <div class="form-group"><label>Refresh Token (optional)</label><input type="text" name="refresh_token" class="form-control"></div>
            <div class="form-group"><label>Account ID (optional)</label><input type="text" name="platform_account_id" class="form-control"></div>
            <div class="form-group"><label>Username (optional)</label><input type="text" name="platform_username" class="form-control"></div>
            <div class="form-group"><label>Display Name (optional)</label><input type="text" name="platform_display_name" class="form-control"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary"><i class="fas fa-plug mr-1"></i> Connect</button> <a href="{{ route('social.accounts.index') }}" class="btn btn-default">Cancel</a></div>
    </form>

<div class="col-md-4"><div class="card"><div class="card-header"><h3 class="card-title">Instructions</h3></div><div class="card-body"><p>Enter your platform access token to connect your account. You can get this token from the platform's developer console.</p><p class="text-muted text-sm">All credentials are encrypted and stored securely.</p></div>
</div>
@endsection

