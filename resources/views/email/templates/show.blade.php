@extends('layouts.unified')
@section('title', $template->name)

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6 space-y-4">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                            <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400 w-32">Subject</th><td class="px-4 py-2 text-gray-900 dark:text-white">{{ $template->subject }}</td></tr>
                            <tr><th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Category</th><td class="px-4 py-2"><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($template->category) }}</span></td></tr>
                        </table>
                    </div>
                    <h5 class="font-semibold text-gray-900 dark:text-white">HTML Content</h5>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">{{ $template->html_content }}</div>
                    @if($template->plain_text_content)
                        <h5 class="font-semibold text-gray-900 dark:text-white mt-3">Plain Text</h5>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">{{ $template->plain_text_content }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
