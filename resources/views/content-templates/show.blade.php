@extends('layouts.unified')
@section('title', 'Template: {{ $template->name }}')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Template: {{ $template->name }}</h3></div>
                <div class="p-6 space-y-4">
                    <p><strong class="text-gray-700 dark:text-gray-300">Platform:</strong> <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($template->platform) }}</span></p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Type:</strong> <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($template->type) }}</span></p>
                    <p><strong class="text-gray-700 dark:text-gray-300">Status:</strong> <span class="px-2 py-1 text-xs font-medium rounded-full {{ $template->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($template->status) }}</span></p>
                    <h5 class="font-medium text-gray-900 dark:text-white">Content</h5>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/50">
                        <pre class="mb-0 text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ $template->template_content }}</pre>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <a href="{{ route('content-templates.edit', $template) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">Edit</a>
                    <form action="{{ route('content-templates.destroy', $template) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection