<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>System Health</h1>
        
        @foreach($health as $service => $status)
            <div class="health-item {{ $status['status'] }}">
                <h3>{{ ucfirst($service) }}</h3>
                <p>{{ $status['message'] }}</p>
                @isset($status['response_time_ms'])
                    <small>Response: {{ $status['response_time_ms'] }}ms</small>
                @endisset
                @isset($status['pending_jobs'])
                    <small>Pending: {{ $status['pending_jobs'] }}</small>
                @endisset
                @isset($status['failed_jobs'])
                    <small>Failed: {{ $status['failed_jobs'] }}</small>
                @endisset
            </div>
        @endforeach
    </div>
</body>
</html>
