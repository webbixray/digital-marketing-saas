<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Audit Log - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('ai.index') }}">AI</a>
                <a class="nav-link active" href="{{ route('ai-audit.index') }}">AI Audit</a>
                <a class="nav-link" href="{{ route('ai-audit.report') }}">Report</a>
                <form action="{{ route('logout') }}" method="POST" class="d-flex">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>AI Audit Log</h1>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('ai-audit.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All</option>
                                @foreach($actions as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['action'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ ucfirst($label) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Compliance Status</label>
                            <select name="compliance_status" class="form-select">
                                <option value="">All</option>
                                <option value="pass" {{ ($filters['compliance_status'] ?? '') === 'pass' ? 'selected' : '' }}>Pass</option>
                                <option value="warn" {{ ($filters['compliance_status'] ?? '') === 'warn' ? 'selected' : '' }}>Warn</option>
                                <option value="fail" {{ ($filters['compliance_status'] ?? '') === 'fail' ? 'selected' : '' }}>Fail</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('ai-audit.index') }}" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="mb-3">
            <a href="{{ route('ai-audit.index', array_merge($filters, ['flagged' => true])) }}" class="btn btn-outline-warning">
                Show Flagged Only
            </a>
            <a href="{{ route('ai-audit.export', ['format' => 'csv']) }}" class="btn btn-outline-secondary">
                Export CSV
            </a>
            <a href="{{ route('ai-audit.export', ['format' => 'json']) }}" class="btn btn-outline-secondary">
                Export JSON
            </a>
        </div>

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
                        <th>Compliance</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                            <td><span class="badge bg-primary">{{ $log->action }}</span></td>
                            <td>{{ $log->model_used ?? 'N/A' }}</td>
                            <td>{{ number_format($log->bias_score, 2) }}</td>
                            <td>{{ number_format($log->toxicity_score, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $log->compliance_status_color }}">
                                    {{ ucfirst($log->compliance_status) }}
                                </span>
                            </td>
                            <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('ai-audit.show', $log->id) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $logs->appends($filters)->links() }}
        </div>
    </div>
</body>
</html>
