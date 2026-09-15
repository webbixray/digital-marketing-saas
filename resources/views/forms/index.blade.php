@extends('layouts.unified')
@section('title', 'Forms')

@section('content')
<div class="space-y-6">
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Forms</h3>
        <div class="card-tools">
            <a href="{{ route('forms.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Form</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead><tr><th>Name</th><th>Status</th><th>Submissions</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($forms as $form)
                    <tr>
                        <td><a href="{{ route('forms.show', $form) }}">{{ $form->name }}</a></td>
                        <td><span class="badge badge-{{ $form->is_published ? 'success' : 'secondary' }}">{{ $form->is_published ? 'Published' : 'Draft' }}</span></td>
                        <td>{{ $form->submissions_count }}</td>
                        <td>
                            <form action="{{ route('forms.toggle', $form) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-xs btn-{{ $form->is_published ? 'warning' : 'success' }}">{{ $form->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <a href="{{ route('forms.edit', $form) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('forms.destroy', $form) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">No forms</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

