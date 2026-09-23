<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Compliance Report - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('ai-audit.index') }}">Audit Log</a>
                <a class="nav-link active" href="{{ route('ai-audit.report') }}">Report</a>
                <form action="{{ route('logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>AI Compliance Report</h1>
        <p class="text-muted">Period: {{ $period }}</p>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title">{{ $report['total_logs'] }}</h3>
                        <p class="card-text text-muted">Total Logs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h3 class="card-title text-success">{{ $report['pass_count'] }}</h3>
                        <p class="card-text text-muted">Passed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h3 class="card-title text-warning">{{ $report['warn_count'] }}</h3>
                        <p class="card-text text-muted">Warnings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h3 class="card-title text-danger">{{ $report['fail_count'] }}</h3>
                        <p class="card-text text-muted">Failed</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Pass Rate</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: {{ $report['pass_rate'] }}%">
                                {{ $report['pass_rate'] }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Average Scores</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <td>Bias Score</td>
                                <td><strong>{{ $report['avg_bias_score'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Toxicity Score</td>
                                <td><strong>{{ $report['avg_toxicity_score'] }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($report['flagged_by_action']))
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Flagged Content by Action</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Flag Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['flagged_by_action'] as $action => $count)
                                <tr>
                                    <td>{{ ucfirst($action) }}</td>
                                    <td><span class="badge bg-danger">{{ $count }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!empty($report['trend']))
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Compliance Trend</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Pass</th>
                                <th>Warn</th>
                                <th>Fail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['trend'] as $date => $counts)
                                <tr>
                                    <td>{{ $date }}</td>
                                    <td class="text-success">{{ $counts['pass'] ?? 0 }}</td>
                                    <td class="text-warning">{{ $counts['warn'] ?? 0 }}</td>
                                    <td class="text-danger">{{ $counts['fail'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-3">
            <a href="{{ route('ai-audit.export', ['format' => 'csv']) }}" class="btn btn-outline-secondary">Export Audit Log (CSV)</a>
            <a href="{{ route('ai-audit.index') }}" class="btn btn-secondary">Back to Audit Log</a>
        </div>
    </div>
</body>
</html>
