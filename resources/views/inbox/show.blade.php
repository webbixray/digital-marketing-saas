<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>{{ ucfirst($message->platform) }} Message</h1>
        <div class="message">
            <p><strong>From:</strong> {{ $message->author_name }}</p>
            <p><strong>Received:</strong> {{ $message->received_at->toDayDateTimeString() }}</p>
            <p>{{ $message->content }}</p>
        </div>
    </div>
</body>
</html>
