@extends('layouts.unified')

@section('title', 'Profile Settings')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
        <p class="text-gray-600 mt-1">Manage your account details, password, and connected accounts</p>
    </div>

    <!-- Profile Information -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Profile Information</h2>
        <form method="POST" action="{{ route('client-portal.v2.profile.update', ['client' => $client->id]) }}">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name', $client->name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $client->email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                    <input type="text" name="company" value="{{ old('company', $client->company) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('company')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <input type="text" name="industry" value="{{ old('industry', $client->industry) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('industry')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="mt-4 bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Save Changes</button>
        </form>
    </div>

    <!-- Password Change -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Change Password</h2>
        <form method="POST" action="{{ route('client-portal.v2.password.update', ['client' => $client->id]) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('current_password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <button type="submit" class="mt-4 bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Update Password</button>
        </form>
    </div>

    <!-- Social Connections -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Connected Social Accounts</h2>

        @if(isset($socialAccounts) && $socialAccounts->count() > 0)
            <div class="space-y-3 mb-6">
                @foreach($socialAccounts as $account)
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                {{ $account->platform === 'facebook' ? 'bg-blue-100 text-blue-600' : '' }}
                                {{ $account->platform === 'twitter' ? 'bg-sky-100 text-sky-600' : '' }}
                                {{ $account->platform === 'instagram' ? 'bg-pink-100 text-pink-600' : '' }}
                                {{ $account->platform === 'linkedin' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ !in_array($account->platform, ['facebook', 'twitter', 'instagram', 'linkedin']) ? 'bg-gray-100 text-gray-600' : '' }}">
                                <i class="fab fa-{{ $account->platform }}"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ ucfirst($account->platform) }}</p>
                                <p class="text-xs text-gray-500">{{ $account->platform_display_name ?? 'Connected' }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('client-portal.v2.social.disconnect', ['client' => $client->id, 'account' => $account->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 text-sm hover:text-red-800">Disconnect</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 mb-6">No social accounts connected yet.</p>
        @endif

        <h3 class="text-sm font-semibold text-gray-900 mb-3">Connect a New Account</h3>
        <form method="POST" action="{{ route('client-portal.v2.social.connect', ['client' => $client->id]) }}">
            @csrf
            <div class="flex space-x-3">
                <select name="platform" class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select platform...</option>
                    <option value="facebook">Facebook</option>
                    <option value="twitter">Twitter / X</option>
                    <option value="instagram">Instagram</option>
                    <option value="linkedin">LinkedIn</option>
                </select>
                <input type="text" name="account_name" placeholder="Account name" class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Connect</button>
            </div>
        </form>
    </div>
</div>
@endsection
