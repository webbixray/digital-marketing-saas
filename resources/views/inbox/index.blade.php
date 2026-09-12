<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Inbox - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Unified Inbox</h1>
        
        <div class="stats">
            <span>Unread: {{ $inbox['unread_count'] ?? 0 }}</span>
            <span>Total: {{ $inbox['total_count'] ?? 0 }}</span>
        </div>
        
        @if(!empty($inbox['messages']))
            <ul>
                @foreach($inbox['messages'] as $message)
                    <li class="{{ $message->status === 'unread' ? 'unread' : '' }}">
                        <strong>{{ ucfirst($message->platform) }}</strong>
                        {{ Str::limit($message->content, 100) }}
                        <small>{{ $message->received_at->diffForHumans() }}</small>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No messages.</p>
        @endif
    </div>
</body>
</html>
