@extends('layouts.unified')
@section('title', 'White-Label Settings')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">White-Label Settings</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Customize your agency branding</p>
        </div>
    </div>

    <!-- Configuration Form -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Configuration</h3>
        </div>
        <form method="POST" action="{{ route('white-label.update') }}" class="p-6 space-y-6">
            @csrf

            <!-- Brand Identity -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="brand_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Name</label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name', $settings->brand_name ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="{{ config('app.name') }}">
                    <p class="text-xs text-gray-500 mt-1">The name displayed in emails and public pages</p>
                    @error('brand_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="brand_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Color</label>
                    <div class="flex gap-2">
                        <input type="color" id="brand_color_picker" value="{{ old('brand_color', $settings->brand_color ?? '#4f46e5') }}"
                            class="w-12 h-10 rounded border border-gray-300 cursor-pointer"
                            onupdate="document.getElementById('brand_color').value = this.value">
                        <input type="text" id="brand_color" name="brand_color" value="{{ old('brand_color', $settings->brand_color ?? '#4f46e5') }}"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="#4f46e5" maxlength="7">
                    </div>
                    @error('brand_color') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                    <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url', $settings->logo_url ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="https://example.com/logo.png">
                    @error('logo_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="favicon_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Favicon URL</label>
                    <input type="url" id="favicon_url" name="favicon_url" value="{{ old('favicon_url', $settings->favicon_url ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="https://example.com/favicon.ico">
                    @error('favicon_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Email Settings -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">Email Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Name</label>
                        <input type="text" id="from_name" name="from_name" value="{{ old('from_name', $settings->from_name ?? '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="{{ config('mail.from.name') }}">
                        @error('from_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Email</label>
                        <input type="email" id="from_email" name="from_email" value="{{ old('from_email', $settings->from_email ?? '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="{{ config('mail.from.address') }}">
                        @error('from_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="email_signature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Signature</label>
                        <textarea id="email_signature" name="email_signature" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Best regards, The Team">{{ old('email_signature', $settings->email_signature ?? '') }}</textarea>
                        @error('email_signature') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Custom CSS -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">Advanced</h4>
                <div>
                    <label for="custom_css" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom CSS</label>
                    <textarea id="custom_css" name="custom_css" rows="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"
                        placeholder="body { background: #f0f0f0; }">{{ old('custom_css', $settings->custom_css ?? '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Custom CSS injected into all agency pages</p>
                    @error('custom_css') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" {{ ($settings->enabled ?? false) ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Enable white-labeling</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="hide_powered_by" value="0">
                        <input type="checkbox" name="hide_powered_by" value="1" {{ ($settings->hide_powered_by ?? false) ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Hide "Powered by" footer</span>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors inline-flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Custom Domain -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Custom Domain</h3>
        </div>
        <div class="p-6">
            @if(isset($settings) && $settings->custom_domain)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Current domain:</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $settings->custom_domain }}</p>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-xs font-medium rounded-full">Active</span>
                </div>
            @else
                <form method="POST" action="{{ route('white-label.domain') }}" class="flex gap-4">
                    @csrf
                    <input type="text" name="domain" required
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="app.your-domain.com">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                        Setup Domain
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">Add a CNAME record pointing to this server, then verify ownership.</p>
            @endif
        </div>
    </div>
</div>
