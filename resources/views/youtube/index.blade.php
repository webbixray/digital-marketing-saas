<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Integration - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>YouTube Integration</h1>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        
        @if($youtubeAccounts->isEmpty())
            <p>No YouTube channels connected.</p>
            <a href="{{ route('youtube.connect') }}" class="btn btn-primary">Connect YouTube Channel</a>
        @else
            <h2>Connected Channels</h2>
            <ul>
                @foreach($youtubeAccounts as $account)
                    <li>
                        {{ $account->platform_display_name ?? $account->platform_username }}
                        ({{ $account->is_active ? 'Active' : 'Inactive' }})
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</body>
</html>
