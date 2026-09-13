<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Failed Jobs - Digital Marketing SaaS</title>
</head>
<body>
    <div class="container">
        <h1>Failed Jobs</h1>
        
        @if(count($failedJobs) > 0)
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Connection</th>
                        <th>Queue</th>
                        <th>Failed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failedJobs as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>{{ $job->connection }}</td>
                            <td>{{ $job->queue }}</td>
                            <td>{{ $job->failed_at }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.retry-job', $job->id) }}" style="display:inline">
                                    @csrf
                                    <button type="submit">Retry</button>
                                </form>
                                <form method="POST" action="{{ route('admin.delete-failed-job', $job->id) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No failed jobs.</p>
        @endif
    </div>
</body>
</html>
