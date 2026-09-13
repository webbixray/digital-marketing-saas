<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="stats">
            <div class="stat">
                <h3>{{ $stats['total_agencies'] ?? 0 }}</h3>
                <p>Total Agencies</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['active_agencies'] ?? 0 }}</h3>
                <p>Active Agencies</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['total_users'] ?? 0 }}</h3>
                <p>Total Users</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['total_social_posts'] ?? 0 }}</h3>
                <p>Total Posts</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['published_posts'] ?? 0 }}</h3>
                <p>Published</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['failed_posts'] ?? 0 }}</h3>
                <p>Failed</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['pending_posts'] ?? 0 }}</h3>
                <p>Pending</p>
            </div>
            <div class="stat">
                <h3>{{ $stats['posts_today'] ?? 0 }}</h3>
                <p>Today</p>
            </div>
        </div>
        
        <h2>Recent Activity</h2>
        @if($recentActivity && count($recentActivity) > 0)
            <ul>
                @foreach($recentActivity as $post)
                    <li>
                        {{ $post->agency->name ?? 'Unknown' }} - {{ $post->platform }} - {{ $post->status }}
                    </li>
                @endforeach
            </ul>
        @else
            <p>No recent activity.</p>
        @endif
        
        <h2>Failed Posts</h2>
        @if($failedPosts && count($failedPosts) > 0)
            <ul>
                @foreach($failedPosts as $post)
                    <li>
                        {{ $post->platform }} - {{ $post->error_message }}
                    </li>
                @endforeach
            </ul>
        @else
            <p>No failed posts.</p>
        @endif
    </div>
</body>
</html>
