<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flagged AI Content - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('ai-audit.index') }}">Audit Log</a>
                <a class="nav-link" href="{{ route('ai-audit.report') }}">Report</a>
                <form action="{{ route('logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Flagged AI Content</h1>
        <p class="text-muted">Content with compliance issues (fail/warn)</p>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>Bias Score</th>
                        <th>Toxicity Score</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flaggedContent as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->model_used ?? 'N/A' }}</td>
                            <td>{{ number_format($log->bias_score, 2) }}</td>
                            <td>{{ number_format($log->toxicity_score, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $log->compliance_status_color }}">
                                    {{ ucfirst($log->compliance_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($log->flagged_reason, 50) }}</td>
                            <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('ai-audit.show', $log->id) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-success">
                                <i class="fas fa-check-circle"></i> No flagged content. All clear!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
