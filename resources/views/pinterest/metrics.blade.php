<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinterest Metrics - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Pinterest Profile: {{ $account->platform_display_name }}</h1>
        
        @if($profile['success'])
            <div class="profile">
                <p>Username: {{ $profile['data']['username'] ?? 'N/A' }}</p>
                <p>Account Type: {{ $profile['data']['account_type'] ?? 'N/A' }}</p>
            </div>
        @endif
        
        @if($boards['success'])
            <h2>Boards</h2>
            <ul>
                @foreach($boards['boards'] as $board)
                    <li>{{ $board['name'] ?? 'Untitled' }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</body>
</html>
