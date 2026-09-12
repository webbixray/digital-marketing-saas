<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Metrics - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>TikTok Profile: {{ $account->platform_display_name }}</h1>
        
        @if($profile['success'])
            <div class="profile">
                <p>Username: {{ $profile['data']['username'] ?? 'N/A' }}</p>
                <p>Display Name: {{ $profile['data']['display_name'] ?? 'N/A' }}</p>
                <p>Bio: {{ $profile['data']['bio_description'] ?? 'N/A' }}</p>
                <hr>
                <p>Followers: {{ $profile['data']['follower_count'] ?? 0 }}</p>
                <p>Following: {{ $profile['data']['following_count'] ?? 0 }}</p>
                <p>Likes: {{ $profile['data']['likes_count'] ?? 0 }}</p>
                <p>Videos: {{ $profile['data']['video_count'] ?? 0 }}</p>
            </div>
        @endif
    </div>
</body>
</html>
