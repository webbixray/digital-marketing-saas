@extends('layouts.unified')
@section('title', 'Edit Landing Page')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4"><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Edit Landing Page</h3></div>
    <form action="{{ route('landing-pages.update', $page) }}" method="POST">@csrf @method('PUT')
        <div class="p-6">
            <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->name }}" required></div>
            <div class="mb-4"><label>Headline</label><input type="text" name="headline" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->headline }}"></div>
            <div class="mb-4"><label>Content</label><textarea name="content" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6">{{ $page->content }}</textarea></div>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>CTA Text</label><input type="text" name="cta_text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->cta_text }}">
                <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>CTA URL</label><input type="url" name="cta_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->cta_url }}">
            </div>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Background</label><input type="color" name="background_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->background_color }}">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Text Color</label><input type="color" name="text_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->text_color }}">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Button Color</label><input type="color" name="button_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->button_color }}">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Button Text</label><input type="color" name="button_text_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $page->button_text_color }}">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Update</button> <a href="{{ route('landing-pages.show', $page) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors">Cancel</a></div>
    </form>
</div>
</div>
@endsection

