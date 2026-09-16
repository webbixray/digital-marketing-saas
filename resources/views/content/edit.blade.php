@extends('layouts.unified')
@section('title', 'Edit Content Asset')
@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Edit Content Asset</h3></div>
    <form action="{{ route('content.update', $asset) }}" method="POST">@csrf @method('PUT')
        <div class="p-6">
            <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $asset->name }}" required></div>
            <div class="mb-4"><label>Content</label><textarea name="content" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6" required>{{ $asset->content }}</textarea></div>
            <div class="mb-4"><label>Media URL</label><input type="url" name="media_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $asset->media_url }}"></div>
            <div class="mb-4"><label>Tags (comma-separated)</label><input type="text" name="tags" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ implode(', ', $asset->tags ?? []) }}"></div>
            <div class="mb-4"><div class="icheck-primary"><input type="checkbox" name="is_public" id="is_public" value="1" {{ $asset->is_public ? 'checked' : '' }}><label for="is_public">Public</label>
        </div>
        <div class="card-footer"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Update</button> <a href="{{ route('content.show', $asset) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

