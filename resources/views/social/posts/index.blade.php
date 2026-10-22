@extends("layouts.unified")

@section('title', 'Posts')

@section('content')
 <x-flash-messages />
 <div class="mb-8 flex items-center justify-between">
  <div>
   <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Social Posts</h2>
   <p class="text-gray-500 dark:text-gray-400 mt-1">Manage and schedule your social media content.</p>
  </div>
  <a href="{{ route('social.posts.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
   <i class="fas fa-plus"></i> New Post
  </a>
 </div>

 <!-- Filters -->
 <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
  <div class="p-6">
   <form method="GET" class="flex flex-wrap gap-4">
    <div class="flex-1 min-w-[200px]">
     <label for="post-status-filter" class="sr-only">Filter by Status</label>
     <select id="post-status-filter" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
      <option value="">All Status</option>
      @foreach($statuses as $key => $label)
       <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
     </select>
    </div>
    <div class="flex-1 min-w-[200px]">
     <label for="post-platform-filter" class="sr-only">Filter by Platform</label>
     <select id="post-platform-filter" name="platform" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
      <option value="">All Platforms</option>
      @foreach($platforms as $key => $label)
       <option value="{{ $key }}" {{ request('platform') === $key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
     </select>
    </div>
    <div class="flex-1 min-w-[200px]">
     <input type="text" name="search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Search content..." value="{{ request('search') }}">
    </div>
    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
     <i class="fas fa-filter"></i> Filter
    </button>
   </form>
  </div>
 </div>

 <!-- Posts Table -->
 <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
     <thead class="bg-gray-50 dark:bg-gray-800/60">
      <tr>
       <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Content</th>
       <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Platform</th>
       <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
       <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scheduled</th>
       <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quality</th>
       <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
      </tr>
     </thead>
     <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
      @forelse($posts as $post)
       <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
        <td class="px-4 py-3">
         <div class="max-w-xs truncate text-sm text-gray-900 dark:text-gray-100" title="{{ $post->content }}">{{ Str::limit($post->content, 60) }}</div>
         @if($post->campaign_id)
         <div class="mt-1"><span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400"><i class="fas fa-folder text-[10px]"></i> {{ $post->campaign->name ?? '' }}</span></div>
         @endif
        </td>
        <td class="px-4 py-3 whitespace-nowrap">
         <div class="flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700">
           <i class="fab fa-{{ $post->platform }} text-sm text-gray-600 dark:text-gray-300"></i>
          </span>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $post->platform }}</span>
         </div>
        </td>
        <td class="px-4 py-3 whitespace-nowrap">
         <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full {{ $post->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : ($post->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : ($post->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300')) }}">
          <span class="w-1.5 h-1.5 rounded-full {{ $post->status === 'published' ? 'bg-green-500' : ($post->status === 'failed' ? 'bg-red-500' : ($post->status === 'scheduled' ? 'bg-yellow-500' : 'bg-blue-500')) }}"></span>
          {{ ucfirst($post->status) }}
         </span>
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
         {{ $post->scheduled_at ? $post->scheduled_at->format('M d, Y H:i') : '—' }}
        </td>
        <td class="px-4 py-3 whitespace-nowrap">
         <div class="flex items-center gap-2">
          <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
           <div class="h-2 rounded-full {{ ($post->quality_score ?? 0) >= 7 ? 'bg-green-500' : (($post->quality_score ?? 0) >= 4 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ ($post->quality_score ?? 0) * 10 }}%"></div>
          </div>
          <span class="text-xs text-gray-500 dark:text-gray-400 w-8 text-right">{{ $post->quality_score ?? 0 }}/10</span>
         </div>
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-right">
         <div class="flex items-center justify-end gap-1">
          <a href="{{ route('social.posts.show', $post) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors" title="View">
           <i class="fas fa-eye text-sm"></i>
          </a>
          <a href="{{ route('social.posts.edit', $post) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors" title="Edit">
           <i class="fas fa-edit text-sm"></i>
          </a>
          <form method="POST" action="{{ route('social.posts.destroy', $post) }}" class="inline">
           @csrf @method('DELETE')
           <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 transition-colors" title="Delete" onclick="return confirm('Are you sure?')">
            <i class="fas fa-trash text-sm"></i>
           </button>
          </form>
         </div>
        </td>
       </tr>
      @empty
       <tr>
        <td colspan="6" class="text-center py-12 text-gray-500 dark:text-gray-400">
         <i class="fas fa-inbox text-5xl mb-4 block text-gray-300 dark:text-gray-600"></i>
         <p class="text-sm font-medium">No posts found</p>
         <p class="text-xs mt-1"><a href="{{ route('social.posts.create') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Create your first post</a> to get started.</p>
        </td>
       </tr>
      @endforelse
     </tbody>
    </table>
  </div>
  @if($posts->hasPages())
   <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
    <p class="text-xs text-gray-500 dark:text-gray-400">Showing {{ $posts->firstItem() }}–{{ $posts->lastItem() }} of {{ $posts->total() }}</p>
    <div class="flex items-center gap-1">
     {{ $posts->onEachSide(1)->links() }}
    </div>
   </div>
  @endif
 </div>
@endsection
