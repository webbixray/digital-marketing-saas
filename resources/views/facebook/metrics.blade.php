<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Metrics - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Facebook Metrics: {{ $account->platform_display_name }}</h1>
        
        @if($page['success'])
            <div class="profile">
                <p>Name: {{ $page['data']['name'] ?? 'N/A' }}</p>
                <p>Category: {{ $page['data']['category'] ?? 'N/A' }}</p>
                <p>Likes: {{ $page['data']['fan_count'] ?? 0 }}</p>
                <p>Website: {{ $page['data']['website'] ?? 'N/A' }}</p>
            </div>
        @endif
        
        @if($insights['success'])
            <div class="insights">
                @foreach($insights['data'] as $name => $metric)
                    <p>{{ $metric['title'] }}: {{ is_array($metric['values']) ? json_encode($metric['values']) : $metric['values'] }}</p>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
