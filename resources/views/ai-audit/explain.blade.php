<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Explanation #{{ $log->id }} - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('ai-audit.show', $log->id) }}">Back to Detail</a>
                <a class="nav-link" href="{{ route('ai-audit.index') }}">Audit Log</a>
                <form action="{{ route('logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>AI Decision Explanation #{{ $log->id }}</h1>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5>Summary</h5>
            </div>
            <div class="card-body">
                <p class="lead">{{ $explanation['summary'] }}</p>
                <p><strong>Confidence:</strong> {{ $explanation['confidence'] }}%</p>
                <p><strong>Model:</strong> {{ $explanation['model'] }}</p>
                <p><strong>Explanation Type:</strong> {{ $explanation['explanation_type'] }}</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Feature Importance</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Value</th>
                            <th>Importance</th>
                            <th>Direction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($explanation['feature_importance']['features'] ?? [] as $feature)
                            <tr>
                                <td>{{ $feature['feature'] }}</td>
                                <td>{{ is_numeric($feature['value']) ? number_format($feature['value'], 3) : $feature['value'] }}</td>
                                <td>{{ number_format($feature['importance_score'], 3) }}</td>
                                <td>
                                    <span class="badge bg-{{ $feature['direction'] === 'positive' ? 'success' : 'secondary' }}">
                                        {{ $feature['direction'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Decision Path</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($explanation['decision_path']['steps'] ?? [] as $step)
                        <li class="list-group-item">
                            <strong>Step {{ $step['step'] }}: {{ $step['name'] }}</strong>
                            <br><small class="text-muted">{{ $step['description'] }}</small>
                            <br><small>Status: <span class="badge bg-success">{{ $step['status'] }}</span></small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Reasoning Steps</h5>
            </div>
            <div class="card-body">
                <ol>
                    @foreach($explanation['reasoning'] ?? [] as $reason)
                        <li class="mb-2">
                            <strong>{{ $reason['description'] }}</strong>
                            <br><small class="text-muted">{{ $reason['detail'] }}</small>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('ai-audit.show', $log->id) }}" class="btn btn-secondary">Back to Detail</a>
        </div>
    </div>
</body>
</html>
