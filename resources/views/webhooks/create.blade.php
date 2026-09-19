@extends('layouts.unified')
@section('title', 'Create Webhook')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Create Webhook</h3></div>
                <form action="{{ route('webhooks.store') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="My Webhook">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                            <input type="url" name="url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="https://example.com/webhook">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Events</label>
                            @foreach($events as $key => $label)
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="events[]" id="event_{{ $key }}" value="{{ $key }}">
                                    <label for="event_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create</button>
                        <a href="{{ route('webhooks.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
