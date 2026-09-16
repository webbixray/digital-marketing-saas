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
            <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg p-4 mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4 mb-4">{{ session('error') }}</div>
        @endif
        
        @if($linkedinAccounts->isEmpty())
            <p>No LinkedIn accounts connected.</p>
            <a href="{{ route('linkedin.connect') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Connect LinkedIn Account</a>
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
