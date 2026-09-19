@extends('layouts.unified')
@section('title', 'Create Form')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Create Form</h3></div>
                <form action="{{ route('forms.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fields (JSON)</label>
                            <textarea name="fields" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6" placeholder='[{"name":"name","type":"text","label":"Full Name","required":true}]' required></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Success Message</label>
                            <input type="text" name="success_message" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Thank you!">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Redirect URL</label>
                            <input type="url" name="redirect_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://...">
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create</button>
                        <a href="{{ route('forms.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
