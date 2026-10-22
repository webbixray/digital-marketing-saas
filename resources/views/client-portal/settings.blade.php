@extends('layouts.unified')
@section('title', 'Client Portal Settings')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Client Portal Settings</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Configure the client portal for your agency.</p>
    </div>

    <form method="POST" action="{{ route('client-portal.settings.update') }}">
        @csrf @method('PUT')
        
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">General Settings</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label for="brand_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Name</label>
                    <input type="text" name="brand_name" id="brand_name" value="{{ old('brand_name', $settings->brand_name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="brand_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Color</label>
                    <input type="color" name="brand_color" id="brand_color" value="{{ old('brand_color', $settings->brand_color) }}" class="h-10 w-20 px-2 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                    <input type="url" name="logo_url" id="logo_url" value="{{ old('logo_url', $settings->logo_url) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://example.com/logo.png">
                </div>
                <div>
                    <label for="custom_domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom Domain (optional)</label>
                    <input type="text" name="custom_domain" id="custom_domain" value="{{ old('custom_domain', $settings->custom_domain) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="portal.youragency.com">
                </div>
                <div>
                    <label for="welcome_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Welcome Message</label>
                    <textarea name="welcome_message" id="welcome_message" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Welcome to your client portal...">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Features</h3>
            </div>
            <div class="p-6 space-y-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="is_enabled" value="1" {{ $settings->is_enabled ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Enable Client Portal</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="show_analytics" value="1" {{ $settings->show_analytics ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Show Analytics</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="show_invoices" value="1" {{ $settings->show_invoices ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Show Invoices</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="allow_approvals" value="1" {{ $settings->allow_approvals ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Allow Post Approvals</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="show_team_activity" value="1" {{ $settings->show_team_activity ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Show Team Activity</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Save Settings</button>
        </div>
    </form>
</div>
@endsection
