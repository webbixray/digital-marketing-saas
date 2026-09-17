@extends('layouts.unified')
@section('title', 'Connect Account')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4><div class="col-span-12 md:col-span-8"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Connect Social Account</h3></div>
 <form action="{{ route('social.accounts.store') }}" method="POST">@csrf
 <div class="p-6">
  <div class="mb-4"><label>Platform</label>
  <select name="platform" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
   <option value="">Select platform...</option>
   @foreach($platforms as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
  </select>
  </div>
  <div class="mb-4"><label>Access Token</label><input type="text" name="access_token" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="Enter platform access token"></div>
  <div class="mb-4"><label>Refresh Token (optional)</label><input type="text" name="refresh_token" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
  <div class="mb-4"><label>Account ID (optional)</label><input type="text" name="platform_account_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
  <div class="mb-4"><label>Username (optional)</label><input type="text" name="platform_username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
  <div class="mb-4"><label>Display Name (optional)</label><input type="text" name="platform_display_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
 </div>
 <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-plug mr-1"></i> Connect</button><a href="{{ route('social.accounts.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 inline-flex items-center gap-2 font-medium transition-colors">Cancel</a></div>
 </form>

<div class="col-span-12 md:col-span-4"><div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700"><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Instructions</h3></div><div class="p-6"><p>Enter your platform access token to connect your account. You can get this token from the platform's developer console.</p><p class="text-gray-500 dark:text-gray-400 text-sm">All credentials are encrypted and stored securely.</p></div>
</div>
@endsection

