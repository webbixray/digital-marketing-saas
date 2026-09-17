@extends('layouts.unified')
@section('title', 'Create Webhook')

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Create Webhook</h3></div>
    <form action="{{ route('webhooks.store') }}" method="POST">@csrf
        <div class="p-6">
            <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="My Webhook"></div>
            <div class="mb-4"><label>URL</label><input type="url" name="url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="https://example.com/webhook"></div>
            <div class="mb-4"><label>Events</label>
                @foreach($events as $key => $label)
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="events[]" id="event_{{ $key }}" value="{{ $key }}">
                        <label for="event_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            <div class="mb-4"><div class="flex items-center gap-2"><input type="checkbox" name="is_active" id="is_active" value="1" checked><label for="is_active">Active</label>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create</button> <a href="{{ route('webhooks.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">Cancel</a></div>
    </form>
</div>
</div>
@endsection

