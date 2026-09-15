@extends('layouts.unified')
@section('title', $form->name)

@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Form Details</h3></div>
            <div class="card-body">
                <strong>Name:</strong> {{ $form->name }}<hr>
                <strong>Status:</strong> <span class="badge badge-{{ $form->is_published ? 'success' : 'secondary' }}">{{ $form->is_published ? 'Published' : 'Draft' }}</span><hr>
                <strong>Submissions:</strong> {{ $form->submissions_count }}<hr>
                <strong>Public URL:</strong> <a href="/f/{{ $form->slug }}" target="_blank">/f/{{ $form->slug }}</a><hr>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Submissions</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Data</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($responses as $response)
                            <tr>
                                <td><pre class="text-sm">{{ json_encode($response->data, JSON_PRETTY_PRINT) }}</pre></td>
                                <td>{{ $response->submitted_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">No submissions</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

