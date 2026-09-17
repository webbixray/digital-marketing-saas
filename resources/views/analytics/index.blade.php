@extends("layouts.unified")

@section('title', 'Analytics')

@section('content')
 <x-flash-messages />
 <div class="mb-8 flex items-center justify-between">
  <div>
   <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h2>
   <p class="text-gray-500 dark:text-gray-400 mt-1">Track your social media performance.</p>
  </div>
  <div class="flex items-center gap-2">
   <a href="{{ route('analytics.index', ['range' => '7']) }}" class="px-3 py-1.5 rounded-lg inline-flex items-center gap-1 text-sm font-medium transition-colors {{ $range == 7 ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">7d</a>
               <a href="{{ route('analytics.index', ['range' => '30']) }}" class="px-3 py-1.5 rounded-lg inline-flex items-center gap-1 text-sm font-medium transition-colors {{ $range == 30 ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">30d</a>
               <a href="{{ route('analytics.index', ['range' => '90']) }}" class="px-3 py-1.5 rounded-lg inline-flex items-center gap-1 text-sm font-medium transition-colors {{ $range == 90 ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }}">90d</a>
  </div>
 </div>

 <!-- Stats Grid -->
 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <div class="stat-card">
   <div class="flex items-center justify-between">
    <div>
     <p class="stat-label">Total Posts</p>
     <p class="stat-value">{{ number_format($postStats['total_posts']) }}</p>
    </div>
    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
     <i class="fas fa-pen-nib text-indigo-600 dark:text-indigo-400 text-xl"></i>
    </div>
   </div>
  </div>

  <div class="stat-card">
   <div class="flex items-center justify-between">
    <div>
     <p class="stat-label">Engagement</p>
     <p class="stat-value">{{ number_format($engagement->total_likes + $engagement->total_shares) }}</p>
    </div>
    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 rounded-xl flex items-center justify-center">
     <i class="fas fa-heart text-pink-600 dark:text-pink-400 text-xl"></i>
    </div>
   </div>
  </div>

  <div class="stat-card">
   <div class="flex items-center justify-between">
    <div>
     <p class="stat-label">Revenue</p>
     <p class="stat-value">${{ number_format($revenueStats['paid'], 0) }}</p>
    </div>
    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
     <i class="fas fa-dollar-sign text-green-600 dark:text-green-400 text-xl"></i>
    </div>
   </div>
  </div>

  <div class="stat-card">
   <div class="flex items-center justify-between">
    <div>
     <p class="stat-label">AI Generations</p>
     <p class="stat-value">{{ number_format($aiStats['total_generations']) }}</p>
    </div>
    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
     <i class="fas fa-robot text-purple-600 dark:text-purple-400 text-xl"></i>
    </div>
   </div>
  </div>
 </div>

 <!-- Platform Performance & Engagement Chart -->
 <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
  <!-- Platform Stats -->
  <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
   <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-white">By Platform</h3>
   </div>
   <div class="p-6">
    <div class="space-y-4">
     @foreach($platformStats as $platform => $stat)
      <div class="flex items-center gap-4">
       <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
        <i class="fab fa-{{ $platform }} text-gray-600 dark:text-gray-300"></i>
       </div>
       <div class="flex-1">
        <div class="flex items-center justify-between mb-1">
         <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $platform }}</span>
         <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($stat->total) }}</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
         <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ ($stat->total / max($postStats['total_posts'], 1)) * 100 }}%"></div>
        </div>
       </div>
      </div>
     @endforeach
     @if($platformStats->isEmpty())
      <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No platform data available</p>
     @endif
    </div>
   </div>
  </div>

  <!-- Best Posts -->
  <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
   <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-white">Top Performing Posts</h3>
   </div>
   <div class="p-6">
    <div class="space-y-4">
     @forelse($bestPosts as $post)
      <div class="flex items-start gap-3">
       <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
        <i class="fab fa-{{ $post->platform }} text-gray-600 dark:text-gray-300 text-xs"></i>
       </div>
       <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ Str::limit($post->content, 80) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
         <i class="fas fa-heart mr-1"></i>{{ $post->likes_count ?? 0 }}
         <i class="fas fa-share ml-2 mr-1"></i>{{ $post->shares_count ?? 0 }}
         <i class="fas fa-eye ml-2 mr-1"></i>{{ $post->views_count ?? 0 }}
        </p>
       </div>
      </div>
     @empty
      <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No published posts yet</p>
     @endforelse
    </div>
   </div>
  </div>
 </div>

 <!-- Campaign & Client Stats -->
 <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="stat-card">
   <div class="text-center">
    <p class="stat-value text-indigo-600 dark:text-indigo-400">{{ $campaignStats['total'] }}</p>
    <p class="stat-label">Total Campaigns</p>
    <p class="text-xs text-gray-400 mt-1">{{ $campaignStats['active'] }} active</p>
   </div>
  </div>
  <div class="stat-card">
   <div class="text-center">
    <p class="stat-value text-green-600 dark:text-green-400">{{ $clientStats['total'] }}</p>
    <p class="stat-label">Total Clients</p>
    <p class="text-xs text-gray-400 mt-1">{{ $clientStats['active'] }} active</p>
   </div>
  </div>
  <div class="stat-card">
   <div class="text-center">
    <p class="stat-value text-purple-600 dark:text-purple-400">${{ number_format($aiStats['total_cost'], 2) }}</p>
    <p class="stat-label">AI Cost</p>
    <p class="text-xs text-gray-400 mt-1">{{ number_format($aiStats['total_tokens']) }} tokens used</p>
   </div>
  </div>
 </div>
@endsection
