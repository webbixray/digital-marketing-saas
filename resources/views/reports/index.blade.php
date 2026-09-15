@extends('layouts.unified')
@section('title', 'Reports')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">Generated Reports</h3>
                        <div>
                            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">All</a>
                        </div>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Name</th><th>Type</th><th>Format</th><th>Schedule</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->name }}</td>
                                <td><span class="badge badge-info">{{ $report->type }}</span></td>
                                <td>{{ strtoupper($report->format) }}</td>
                                <td>{{ ucfirst($report->schedule) }}</td>
                                <td><span class="badge badge-{{ $report->status === 'completed' ? 'success' : 'secondary' }}">{{ ucfirst($report->status) }}</span></td>
                                <td>
                                    @if($report->file_path)<a href="{{ route('reports.download', $report) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>@endif
                                    <form action="{{ route('reports.destroy', $report) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this report?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">No reports found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $reports->links() }}</div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

