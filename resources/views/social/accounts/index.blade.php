@extends("layouts.unified")

@section('title', 'Social Accounts')

@section('content')
 <x-flash-messages />
 <div class="mb-8 flex items-center justify-between">
 <div>
  <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Connected Accounts</h2>
  <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your social media platform connections.</p>
 </div>
 <a href="{{ route('social.accounts.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
  <i class="fas fa-plus"></i> Connect Account
 </a>
 </div>

 <!-- Account Cards -->
 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
 @forelse($accounts as $account)
  <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
  <div class="p-6">
   <div class="flex items-center gap-4 mb-4">
   <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $account->is_active ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
    @switch($account->platform)
    @case('facebook')<i class="fab fa-facebook text-blue-600 text-2xl"></i>@break
    @case('instagram')<i class="fab fa-instagram text-pink-600 text-2xl"></i>@break
    @case('twitter')<i class="fab fa-twitter text-blue-400 text-2xl"></i>@break
    @case('linkedin')<i class="fab fa-linkedin text-blue-700 text-2xl"></i>@break
    @case('tiktok')<i class="fab fa-tiktok text-gray-900 dark:text-white text-2xl"></i>@break
    @case('pinterest')<i class="fab fa-pinterest text-red-600 text-2xl"></i>@break
    @case('youtube')<i class="fab fa-youtube text-red-600 text-2xl"></i>@break
    @default<i class="fas fa-share-alt text-gray-600 text-2xl"></i>@break
    @endswitch
   </div>
   <div class="flex-1">
    <h3 class="font-semibold text-gray-900 dark:text-white capitalize">{{ $account->platform }}</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->platform_display_name ?? $account->platform_username ?? 'Connected' }}</p>
   </div>
   </div>

   <div class="flex items-center justify-between mb-4">
   <span class="badge {{ $account->is_active ? 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300' }}">
    {{ $account->is_active ? 'Active' : 'Inactive' }}
   </span>
   <span class="text-xs text-gray-400">Connected {{ $account->created_at->diffForHumans() }}</span>
   </div>

   <div class="flex gap-2">
   <a href="{{ route('social.accounts.edit', $account) }}" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-1 text-sm font-medium transition-colors text-gray-700 dark:text-gray-200 flex-1">
    <i class="fas fa-edit"></i> Edit
   </a>
   <form method="POST" action="{{ route('social.accounts.destroy', $account) }}" class="inline">
    @csrf @method('DELETE')
    <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 inline-flex items-center gap-1 text-sm font-medium transition-colors" onclick="return confirm('Disconnect this account?')">
    <i class="fas fa-unlink"></i>
    </button>
   </form>
   </div>
  </div>
  </div>
 @empty
  <div class="col-span-3 bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="p-6 text-center py-12">
   <i class="fas fa-share-alt text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
   <h3 class="font-semibold text-gray-900 dark:text-white mb-2">No accounts connected</h3>
   <p class="text-gray-500 dark:text-gray-400 mb-4">Connect your social media accounts to start posting.</p>
   <a href="{{ route('social.accounts.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
   <i class="fas fa-plus"></i> Connect Account
   </a>
  </div>
  </div>
 @endforelse
 </div>
@endsection
