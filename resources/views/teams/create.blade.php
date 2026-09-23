@extends('layouts.unified')

@section('title', 'Create Team')

@section('content')
<div x-data="createTeam()" class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
            <a href="{{ route('teams.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Teams</a>
            <i class="fas fa-chevron-right text-xs mx-2"></i>
            <span class="text-gray-900 dark:text-white font-medium">Create</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Team</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Set up a new team and add initial members</p>
    </div>

    <!-- Form -->
    <form action="{{ route('teams.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <!-- Team Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Team Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                       placeholder="e.g., Marketing Team" required>
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
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="Brief description of this teams purpose">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>

            <!-- Add Members (Optional) -->
            <div x-data="{ showMembers: false }">
                <button type="button" @click="showMembers = !showMembers"
                        class="flex items-center gap-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                    <i class="fas" :class="showMembers ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                    Add Existing Members (Optional)
                </button>
                <div x-show="showMembers" x-transition class="mt-4 space-y-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                    @forelse($members as $member)
                        <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" name="member_ids[]" value="{{ $member->id }}"
                                   class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $member->name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $member->email }}</span>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No available members to add</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('teams.index') }}"
               class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                Create Team
            </button>
        </div>
    </form>
</div>
