<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Integration - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Facebook Integration</h1>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        
        @if($facebookAccounts->isEmpty())
            <p>No Facebook pages connected.</p>
            <a href="{{ route('facebook.connect') }}" class="btn btn-primary">Connect Facebook Page</a>
        @else
            <h2>Connected Pages</h2>
            <ul>
                @foreach($facebookAccounts as $account)
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
