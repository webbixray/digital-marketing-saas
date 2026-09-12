<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Integration - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>LinkedIn Integration</h1>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        
        @if($linkedinAccounts->isEmpty())
            <p>No LinkedIn accounts connected.</p>
            <a href="{{ route('linkedin.connect') }}" class="btn btn-primary">Connect LinkedIn Account</a>
        @else
            <h2>Connected Accounts</h2>
            <ul>
                @foreach($linkedinAccounts as $account)
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
