@extends('layouts.unified')
@section('title', $form->name)

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Form Details</h3></div>
            <div class="p-6">
                <strong>Name:</strong> {{ $form->name }}<hr>
                <strong>Status:</strong> <span class="{{ $form->is_published ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $form->is_published ? 'Published' : 'Draft' }}</span><hr>
                <strong>Submissions:</strong> {{ $form->submissions_count }}<hr>
                <strong>Public URL:</strong> <a href="/f/{{ $form->slug }}" target="_blank">/f/{{ $form->slug }}</a><hr>
            </div>
        </div>
    </div>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Submissions</h3></div>
            <div class="p-0">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead><tr><th>Data</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($responses as $response)
                            <tr>
                                <td><pre class="text-sm">{{ json_encode($response->data, JSON_PRETTY_PRINT) }}</pre></td>
                                <td>{{ $response->submitted_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-gray-500 dark:text-gray-400">No submissions</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

