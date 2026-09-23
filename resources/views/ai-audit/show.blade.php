<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Audit Detail #{{ $log->id }} - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('ai-audit.index') }}">Back to List</a>
                <a class="nav-link" href="{{ route('ai-audit.explain', $log->id) }}">Explain</a>
                <form action="{{ route('logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>AI Audit Detail #{{ $log->id }}</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><th>ID</th><td>{{ $log->id }}</td></tr>
                            <tr><th>User</th><td>{{ $log->user?->name ?? 'Unknown' }} ({{ $log->user?->email }})</td></tr>
                            <tr><th>Action</th><td><span class="badge bg-primary">{{ $log->action }}</span></td></tr>
                            <tr><th>Model Used</th><td>{{ $log->model_used ?? 'N/A' }}</td></tr>
                            <tr><th>Created At</th><td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Scores & Compliance</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Bias Score</th>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $log->bias_score >= 0.3 ? 'danger' : 'success' }}"
                                             style="width: {{ $log->bias_score * 100 }}%">
                                            {{ number_format($log->bias_score, 2) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Toxicity Score</th>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $log->toxicity_score >= 0.2 ? 'danger' : 'success' }}"
                                             style="width: {{ $log->toxicity_score * 100 }}%">
                                            {{ number_format($log->toxicity_score, 2) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Compliance Status</th>
                                <td>
                                    <span class="badge bg-{{ $log->compliance_status_color }} fs-6">
                                        {{ ucfirst($log->compliance_status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Flagged Reason</th>
                                <td>{{ $log->flagged_reason ?? 'None' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5>Input/Output Hashes</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr><th>Input Hash</th><td><code>{{ $log->input_hash }}</code></td></tr>
                    <tr><th>Output Hash</th><td><code>{{ $log->output_hash }}</code></td></tr>
                </table>
            </div>
        </div>

        @if($log->metadata)
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Metadata</h5>
                </div>
                <div class="card-body">
                    <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        @endif

        <div class="mt-3">
            <a href="{{ route('ai-audit.explain', $log->id) }}" class="btn btn-info">Explain This Decision</a>
            <a href="{{ route('ai-audit.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</body>
</html>
