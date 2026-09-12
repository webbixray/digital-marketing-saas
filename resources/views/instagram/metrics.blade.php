<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Metrics - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Instagram Metrics: {{ $account->platform_display_name }}</h1>
        
        @if($profile['success'])
            <div class="profile">
                <p>Username: {{ $profile['data']['username'] ?? 'N/A' }}</p>
                <p>Followers: {{ $profile['data']['followers_count'] ?? 0 }}</p>
                <p>Following: {{ $profile['data']['follows_count'] ?? 0 }}</p>
                <p>Media: {{ $profile['data']['media_count'] ?? 0 }}</p>
            </div>
        @endif
        
        @if($insights['success'])
            <div class="insights">
                @foreach($insights['data'] as $metric)
                    <p>{{ $metric['title'] }}: {{ $metric['values'][0]['value'] ?? 0 }}</p>
                @endforeach>
            </div>
        @endif
    </div>
</body>
</html>
