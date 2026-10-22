@extends('layouts.unified')
@section('title', $reseller->name . ' - Settings')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reseller->name }} - White-Label Settings</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Configure custom domain, branding, and white-label preferences.</p>
        </div>
        <a href="{{ route('resellers.show', $reseller) }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">Back to Reseller</a>
    </div>

    <form method="POST" action="{{ route('resellers.settings.update', $reseller) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Agency Name & Slug -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Agency Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agency Name</label>
                    <input type="text" name="settings[brand_name]" value="{{ old('settings.brand_name', $settings['brand_name'] ?? $reseller->name) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('settings.brand_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <input type="text" value="{{ $reseller->slug }}" disabled class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 dark:text-gray-400">
                </div>
            </div>
        </div>

        <!-- Custom Domain -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Custom Domain</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Domain</label>
                    <input type="text" name="domain" value="{{ old('domain', $reseller->domain) }}" placeholder="brand.example.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('domain')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <div class="mt-2">
                        @if($reseller->domains->where('is_verified', true)->count() > 0)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span> Verified
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span> Pending Verification
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo Upload -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Logo</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                    <input type="url" name="settings[logo_url]" value="{{ old('settings.logo_url', $settings['logo_url'] ?? $reseller->logo_url) }}" placeholder="https://example.com/logo.png" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('settings.logo_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Favicon URL</label>
                    <input type="url" name="settings[favicon_url]" value="{{ old('settings.favicon_url', $settings['favicon_url'] ?? '') }}" placeholder="https://example.com/favicon.ico" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('settings.favicon_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            @if($reseller->logo_url)
            <div class="mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Current Logo:</p>
                <img src="{{ $reseller->logo_url }}" alt="Logo" class="h-16 w-auto rounded border border-gray-200 dark:border-gray-600">
            </div>
            @endif
        </div>

        <!-- Brand Colors -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Brand Colors</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Primary Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="primary_color" value="{{ old('primary_color', $reseller->primary_color ?? '#4f46e5') }}" class="h-10 w-14 border border-gray-300 dark:border-gray-600 rounded cursor-pointer">
                        <input type="text" name="settings[brand_color]" value="{{ old('settings.brand_color', $settings['brand_color'] ?? $reseller->primary_color ?? '#4f46e5') }}" placeholder="#4f46e5" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    @error('settings.brand_color')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preview</label>
                    <div class="h-10 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-center text-white text-sm font-medium" style="background-color: {{ $settings['brand_color'] ?? $reseller->primary_color ?? '#4f46e5' }}">
                        Brand Preview
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Email Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Name</label>
                    <input type="text" name="settings[from_name]" value="{{ old('settings.from_name', $settings['from_name'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('settings.from_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Email</label>
                    <input type="email" name="settings[from_email]" value="{{ old('settings.from_email', $settings['from_email'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('settings.from_email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Display Options -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Display Options</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Hide "Powered By" Badge</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Remove the platform branding from the white-label portal.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="settings[hide_powered_by]" value="1" {{ old('settings.hide_powered_by', $settings['hide_powered_by'] ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Enable White-Label Portal</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Allow this reseller to access the white-label portal.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="settings[enabled]" value="1" {{ old('settings.enabled', $settings['enabled'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">Save Settings</button>
        </div>
    </form>
</div>
@endsection
