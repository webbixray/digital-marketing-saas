@extends('layouts.unified')
@section('title', 'Edit Template')
@section('content')
<x-flash-messages />
<div class="max-w-3xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('content-templates.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Templates</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-white">Edit: {{ $template->name }}</span>
    </nav>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Edit Template</h3>
        </div>
        <form action="{{ route('content-templates.update', $template) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4">
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $template->name) }}" required placeholder="Template name">
                </div>
                <div>
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-input" required>
                        <option value="">Select type...</option>
                        <option value="post" {{ old('type', $template->type) === 'post' ? 'selected' : '' }}>Post</option>
                        <option value="email" {{ old('type', $template->type) === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="landing_page" {{ old('type', $template->type) === 'landing_page' ? 'selected' : '' }}>Landing Page</option>
                    </select>
                </div>
                <div>
                    <label for="content" class="form-label">Content</label>
                    <textarea name="content" id="content" class="form-input" rows="12" placeholder="Write your template content here... Use variables like @{{ name }}, @{{ email }}, etc.">{{ old('content', $template->content) }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_public" id="is_public" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" value="1" {{ old('is_public', $template->is_public) ? 'checked' : '' }}>
                    <label for="is_public" class="text-sm font-medium text-gray-700 dark:text-gray-300">Public (shared with all team members)</label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="submit" class="btn-primary">Update Template</button>
                <a href="{{ route('content-templates.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection