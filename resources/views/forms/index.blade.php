@extends('layouts.unified')
@section('title', 'Forms')

@section('content')
<div class="space-y-6">
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-alt mr-2"></i>Forms</h3>
        <div class="card-tools">
            <a href="{{ route('forms.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"><i class="fas fa-plus mr-1"></i> New Form</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
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

