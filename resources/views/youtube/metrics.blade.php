<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Metrics - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>YouTube Channel: {{ $account->platform_display_name }}</h1>
        
        @if($channelStats['success'])
            <div class="stats">
                <p>Subscribers: {{ $channelStats['data']['subscriber_count'] ?? 0 }}</p>
                <p>Videos: {{ $channelStats['data']['video_count'] ?? 0 }}</p>
                <p>Total Views: {{ $channelStats['data']['view_count'] ?? 0 }}</p>
            </div>
        @endif
        
        @if($videos['success'])
            <h2>Recent Videos</h2>
            <ul>
                @foreach($videos['videos'] as $video)
                    <li>
                        {{ $video['title'] }} — {{ $video['view_count'] }} views
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</body>
</html>
