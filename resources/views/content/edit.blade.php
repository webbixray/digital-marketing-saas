@extends('layouts.unified')
@section('title', 'Edit Content Asset')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Edit Content Asset</h3></div>
                <form action="{{ route('content.update', $asset) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="p-6 space-y-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $asset->name }}" required></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label><textarea name="content" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6" required>{{ $asset->content }}</textarea></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Media URL</label><input type="url" name="media_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $asset->media_url }}"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (comma-separated)</label><input type="text" name="tags" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ implode(', ', $asset->tags ?? []) }}"></div>
                        <div class="flex items-center gap-2"><input type="checkbox" name="is_public" id="is_public" value="1" {{ $asset->is_public ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"><label for="is_public" class="text-sm text-gray-700 dark:text-gray-300">Public</label></div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Update</button>
                        <a href="{{ route('content.show', $asset) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection