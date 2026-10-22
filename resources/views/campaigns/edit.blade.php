@extends('layouts.unified')
@section('title', 'Edit Campaign')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Edit Campaign</h3></div>
                <form action="{{ route('campaigns.update', $campaign) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="p-6 space-y-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->name }}" required></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label><select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">@foreach($types as $key => $label)<option value="{{ $key }}" {{ $campaign->type === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label><textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3">{{ $campaign->description }}</textarea></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objective</label><input type="text" name="objective" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->objective }}"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Audience</label><input type="text" name="target_audience" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->target_audience }}"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client</label><select name="client_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><option value="">None</option>@foreach($clients as $client)<option value="{{ $client->id }}" {{ $campaign->client_id === $client->id ? 'selected' : '' }}>{{ $client->name }}</option>@endforeach</select></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label><input type="date" name="start_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->start_date?->format('Y-m-d') }}"></div>
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label><input type="date" name="end_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->end_date?->format('Y-m-d') }}"></div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Update</button>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection