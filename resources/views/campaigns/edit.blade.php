@extends('layouts.unified')
@section('title', 'Edit Campaign')
@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Edit Campaign</h3></div>
    <form action="{{ route('campaigns.update', $campaign) }}" method="POST">@csrf @method('PUT')
        <div class="p-6">
            <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->name }}" required></div>
            <div class="mb-4"><label>Type</label><select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">@foreach($types as $key => $label)<option value="{{ $key }}" {{ $campaign->type === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="mb-4"><label>Description</label><textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3">{{ $campaign->description }}</textarea></div>
            <div class="mb-4"><label>Objective</label><input type="text" name="objective" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->objective }}"></div>
            <div class="mb-4"><label>Target Audience</label><input type="text" name="target_audience" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->target_audience }}"></div>
            <div class="mb-4"><label>Client</label><select name="client_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><option value="">None</option>@foreach($clients as $client)<option value="{{ $client->id }}" {{ $campaign->client_id === $client->id ? 'selected' : '' }}>{{ $client->name }}</option>@endforeach</select></div>
            <div class="grid grid-cols-12 gap-4><div class="col-span-12 md:col-span-6"><div class="mb-4"><label>Start Date</label><input type="date" name="start_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->start_date?->format('Y-m-d') }}">
            <div class="col-span-12 md:col-span-6"><div class="mb-4"><label>End Date</label><input type="date" name="end_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $campaign->end_date?->format('Y-m-d') }}"></div>
        </div>
        <div class="card-footer"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Update</button> <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

