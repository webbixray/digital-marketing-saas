@extends('layouts.unified')
@section('title', 'Email Templates')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                    <tr>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->subject }}</td>
                        <td><span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ ucfirst($template->category) }}</span></td>
                        <td>
                            <a href="{{ route('email.templates.show', $template) }}" class="px-4 py-2 border border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 text-sm font-medium transition-colors">View</a>
                            <a href="{{ route('email.templates.edit', $template) }}" class="px-4 py-2 border border-yellow-300 dark:border-yellow-600 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20 text-sm font-medium transition-colors">Edit</a>
                            <form action="{{ route('email.templates.destroy', $template) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-4 py-2 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $templates->links() }}
        </div>
    </div>
</div>
