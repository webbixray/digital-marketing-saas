@extends('layouts.unified')
@section('title', 'Landing Pages')

@section('content')
<div class="space-y-6">
<div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Landing Pages</h3>
        <div class="card-tools">
            <a href="{{ route('landing-pages.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i> New Landing Page
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Headline</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Conversions</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($landingPages ?? [] as $page)
                    <tr>
                        <td>
                            <a href="{{ route('landing-pages.show', $page) }}">{{ $page->name }}</a>
                        </td>
                        <td>{{ \Str::limit($page->headline ?? '-', 50) }}</td>
                        <td>
                            <span class="badge badge-{{ $page->status === 'published' ? 'success' : ($page->status === 'draft' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($page->status ?? 'draft') }}
                            </span>
                        </td>
                        <td>{{ number_format($page->views_count ?? 0) }}</td>
                        <td>{{ number_format($page->conversions_count ?? 0) }}</td>
                        <td>{{ $page->created_at->diffForHumans() ?? '-' }}</td>
                        <td>
                            <a href="{{ route('landing-pages.edit', $page) }}" class="btn btn-xs btn-default"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('landing-pages.destroy', $page) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No landing pages yet. <a href="{{ route('landing-pages.create') }}">Create your first one</a></p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
