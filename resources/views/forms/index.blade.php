@extends('layouts.unified')
@section('title', 'Forms')

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-alt mr-2"></i>Forms</h3>
        <a href="{{ route('forms.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm"><i class="fas fa-plus mr-1"></i> New Form</a>
    </div>
    <div class="p-0">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead><tr><th>Name</th><th>Status</th><th>Submissions</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($forms as $form)
                    <tr>
                        <td><a href="{{ route('forms.show', $form) }}">{{ $form->name }}</a></td>
                        <td><span class="{{ $form->is_published ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $form->is_published ? 'Published' : 'Draft' }}</span></td>
                        <td>{{ $form->submissions_count }}</td>
                        <td>
                            <form action="{{ route('forms.toggle', $form) }}" method="POST" class="inline">
                                @csrf
                                <button class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">{{ $form->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            <a href="{{ route('forms.edit', $form) }}" class="px-4 py-2 border border-yellow-300 dark:border-yellow-600 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20 text-sm font-medium transition-colors inline-flex items-center gap-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('forms.destroy', $form) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-4 py-2 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors inline-flex items-center gap-1" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500 dark:text-gray-400">No forms</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
</div>
@endsection
