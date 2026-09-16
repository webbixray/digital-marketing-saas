@extends('layouts.unified')
@section('title', 'Create Landing Page')
@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Create Landing Page</h3></div>
    <form action="{{ route('landing-pages.store') }}" method="POST">@csrf
        <div class="p-6">
            <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></div>
            <div class="mb-4"><label>Headline</label><input type="text" name="headline" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Your compelling headline"></div>
            <div class="mb-4"><label>Content</label><textarea name="content" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6" placeholder="Page content (HTML supported)"></textarea></div>
            <div class="grid grid-cols-12 gap-4>
                <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>CTA Text</label><input type="text" name="cta_text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Get Started">
                <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>CTA URL</label><input type="url" name="cta_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://...">
            </div>
            <div class="grid grid-cols-12 gap-4>
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Background</label><input type="color" name="background_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="#ffffff">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Text Color</label><input type="color" name="text_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="#333333">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Button Color</label><input type="color" name="button_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="#007bff">
                <div class="col-span-12 md:col-span-3"><div class="mb-4"><label>Button Text</label><input type="color" name="button_text_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="#ffffff">
            </div>
        </div>
        <div class="card-footer"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create</button> <a href="{{ route('landing-pages.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

