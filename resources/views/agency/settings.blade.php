@extends('layouts.unified')
@section('title', 'Agency Settings')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agency Settings</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your agency profile and preferences.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Settings Navigation -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <nav class="space-y-1">
                        <a href="#profile" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400">
                            <i class="fas fa-building"></i> Agency Profile
                        </a>
                        <a href="#billing" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            <i class="fas fa-credit-card"></i> Billing
                        </a>
                        <a href="#team" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            <i class="fas fa-users"></i> Team Members
                        </a>
                        <a href="#integrations" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            <i class="fas fa-plug"></i> Integrations
                        </a>
                        <a href="#api" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            <i class="fas fa-code"></i> API Keys
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('agency.settings.update') }}">
                @csrf @method('PUT')

                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Agency Profile</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="agency_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agency Name</label>
                            <input type="text" name="agency_name" id="agency_name" value="{{ old('agency_name', $agency->name ?? '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('agency_name')
                                <div class="form-error text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                            <input type="url" name="website" id="website" value="{{ old('website', $agency->website ?? '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://example.com">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $agency->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Branding</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Primary Color</label>
                            <input type="color" name="primary_color" id="primary_color" value="{{ old('primary_color', $agency->primary_color ?? '#4f46e5') }}" class="h-10 w-20 px-2 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label for="logo_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                            <input type="url" name="logo_url" id="logo_url" value="{{ old('logo_url', $agency->logo_url ?? '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
 @endsection
