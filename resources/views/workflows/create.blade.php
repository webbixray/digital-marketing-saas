@extends('layouts.unified')
@section('title', 'Create Workflow')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4"><div class="md:col-span-10"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Create Workflow</h3></div>
    <form action="{{ route('workflows.store') }}" method="POST">@csrf
        <div class="p-6">
            <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></div>
            <div class="mb-4"><label>Trigger</label>
                <select name="trigger_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="triggerSelect">
                    @foreach($triggerTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="mb-4"><label>Actions (JSON)</label>
                <textarea name="actions" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="6" placeholder='[{"type":"send_notification","config":{"message":"New post published"}}]'>{{ old('actions') }}</textarea>
                <small class="text-gray-500 dark:text-gray-400">Available actions: {{ implode(', ', array_keys($actionTypes)) }}</small>
            </div>
            <div class="mb-4"><label>Conditions (JSON, optional)</label>
                <textarea name="conditions" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3" placeholder='{"platform":"facebook"}'>{{ old('conditions') }}</textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Create</button> <a href="{{ route('workflows.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors">Cancel</a></div>
    </form>
</div>
</div>
@endsection

