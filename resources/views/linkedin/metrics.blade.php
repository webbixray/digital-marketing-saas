<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn Metrics - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>LinkedIn Profile: {{ $account->platform_display_name }}</h1>
        
        @if($profile['success'])
            <div class="profile">
                <p>Name: {{ $profile['data']['name'] ?? 'N/A' }}</p>
                <p>Email: {{ $profile['data']['email'] ?? 'N/A' }}</p>
            </div>
        @endif
    </div>
</body>
</html>
