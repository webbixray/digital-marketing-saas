@extends('layouts.unified')
@section('title', 'Create Post')

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
 <div class="md:col-span-2">
 <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
  <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-plus-circle mr-2"></i>Create New Post</h3>
  </div>
  <form action="{{ route('social.posts.store') }}" method="POST">
  @csrf
  <div class="p-6">
   <div class="mb-4">
   <label>Social Account</label>
   <select name="social_account_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('social_account_id') is-invalid @enderror" required>
    <option value="">Select an account...</option>
    @foreach($accounts as $account)
    <option value="{{ $account->id }}" {{ old('social_account_id') == $account->id ? 'selected' : '' }}>
     {{ ucfirst($account->platform) }} - {{ $account->platform_display_name ?? 'Account' }}
    </option>
    @endforeach
   </select>
   @error('social_account_id') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
   </div>
   <div class="mb-4">
   <label>Content</label>
   <textarea name="content" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('content') is-invalid @enderror" required placeholder="Write your post content...">{{ old('content') }}</textarea>
   @error('content') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
   </div>
   <div class="mb-4">
   <label>Hashtags (comma-separated)</label>
   <input type="text" name="hashtags[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="#marketing,#socialmedia" value="{{ old('hashtags') }}">
   </div>
   <div class="mb-4">
   <label>Schedule (optional)</label>
   <input type="datetime-local" name="scheduled_at" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('scheduled_at') }}">
   <small class="text-gray-500 dark:text-gray-400">Leave empty to save as draft</small>
   </div>
   @if($campaigns->count() > 0)
   <div class="mb-4">
    <label>Attach to Campaign</label>
    <select name="campaign_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    <option value="">None</option>
    @foreach($campaigns as $campaign)
     <option value="{{ $campaign->id }}" {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>{{ $campaign->name }}</option>
    @endforeach
    </select>
   </div>
   @endif
  </div>
  <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
   <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-save mr-1"></i> Save Post</button>
   <a href="{{ route('social.posts.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 inline-flex items-center gap-2 font-medium transition-colors">Cancel</a>
  </div>
  </form>
 </div>
 </div>
 <div class="col-span-12 md:col-span-4">
 <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Tips</h3></div>
  <div class="p-6">
  <ul class="text-sm text-gray-500 dark:text-gray-400">
   <li>Use relevant hashtags for visibility</li>
   <li>Best posting times: 9AM, 12PM, 6PM</li>
   <li>Include a clear call-to-action</li>
   <li>Add media for higher engagement</li>
   <li>Character limit varies by platform</li>
  </ul>
  </div>
 </div>
 </div>
</div>
</div>
@endsection

