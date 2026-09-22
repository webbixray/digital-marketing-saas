@extends('layouts.unified')
@section('title', 'Agency Settings')

@section('content')
<x-flash-messages />
<div class="space-y-6" x-data="{ activeTab: 'profile' }">
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
                        <button type="button" @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-building w-5 text-center"></i> Agency Profile
                        </button>
                        <button type="button" @click="activeTab = 'billing'" :class="activeTab === 'billing' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-credit-card w-5 text-center"></i> Billing
                        </button>
                        <button type="button" @click="activeTab = 'team'" :class="activeTab === 'team' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-users w-5 text-center"></i> Team Members
                        </button>
                        <button type="button" @click="activeTab = 'integrations'" :class="activeTab === 'integrations' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-plug w-5 text-center"></i> Integrations
                        </button>
                        <button type="button" @click="activeTab = 'api'" :class="activeTab === 'api' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors">
                            <i class="fas fa-code w-5 text-center"></i> API Keys
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('agency.settings.update') }}">
                @csrf @method('PUT')

                <!-- Profile Tab -->
                <div x-show="activeTab === 'profile'" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
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

                <!-- Branding Tab (also under profile) -->
                <div x-show="activeTab === 'profile'" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
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

                <!-- Billing Tab -->
                <div x-show="activeTab === 'billing'" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Billing & Subscription</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-500 dark:text-gray-400">Manage your subscription plan and billing details.</p>
                        <a href="{{ route('agency.billing') }}" class="inline-flex items-center gap-2 mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                            <i class="fas fa-credit-card"></i> Go to Billing
                        </a>
                    </div>
                </div>

                <!-- Team Tab -->
                <div x-show="activeTab === 'team'" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Team Members</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-500 dark:text-gray-400">Invite and manage your team members.</p>
                        <a href="{{ route('agency.team') }}" class="inline-flex items-center gap-2 mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                            <i class="fas fa-users"></i> Manage Team
                        </a>
                    </div>
                </div>

                <!-- Integrations Tab -->
                <div x-show="activeTab === 'integrations'" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Integrations</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fab fa-facebook text-blue-600 text-xl"></i>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Facebook</p>
                                    <p class="text-xs text-gray-500">Connect your Facebook account</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Connected</span>
                        </div>
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fab fa-instagram text-pink-600 text-xl"></i>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Instagram</p>
                                    <p class="text-xs text-gray-500">Connect your Instagram account</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Pending</span>
                        </div>
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fab fa-twitter text-blue-400 text-xl"></i>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Twitter / X</p>
                                    <p class="text-xs text-gray-500">Connect your Twitter account</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Not Connected</span>
                        </div>
                    </div>
                </div>

                <!-- API Keys Tab -->
                <div x-show="activeTab === 'api'" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">API Keys</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Generate and manage API keys for programmatic access.</p>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 font-mono text-sm text-gray-700 dark:text-gray-300">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Your API Key:</p>
                            <code class="text-indigo-600 dark:text-indigo-400">sk_live_xxxxxxxxxxxxxxxxxxxxxxxx</code>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end" x-show="activeTab === 'profile'">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
 @endsection