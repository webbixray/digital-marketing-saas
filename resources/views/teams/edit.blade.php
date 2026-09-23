@extends('layouts.unified')

@section('title', 'Edit Team')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
            <a href="{{ route('teams.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Teams</a>
            <i class="fas fa-chevron-right text-xs mx-2"></i>
            <a href="{{ route('teams.show', $team) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $team->name }}</a>
            <i class="fas fa-chevron-right text-xs mx-2"></i>
            <span class="text-gray-900 dark:text-white font-medium">Edit</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Team: {{ $team->name }}</h1>
    </div>

    <!-- Form -->
    <form action="{{ route('teams.update', $team) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <!-- Team Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Team Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $team->name) }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Description
                </label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $team->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $team->is_active ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>

            <!-- Owner Info -->
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Owner: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $team->owner?->name ?? 'Unknown' }}</span>
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="{{ route('teams.show', $team) }}"
               class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
