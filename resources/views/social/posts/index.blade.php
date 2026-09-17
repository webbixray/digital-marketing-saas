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
     <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
      <option value="">All Status</option>
      @foreach($statuses as $key => $label)
       <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
     </select>
    </div>
    <div class="flex-1 min-w-[200px]">
     <select name="platform" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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
   <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead>
     <tr>
      <th>Content</th>
      <th>Platform</th>
      <th>Status</th>
      <th>Scheduled</th>
      <th>Quality</th>
      <th>Actions</th>
     </tr>
    </thead>
    <tbody>
     @forelse($posts as $post)
      <tr>
       <td>
        <div class="max-w-xs truncate">{{ Str::limit($post->content, 60) }}</div>
       </td>
       <td>
        <div class="flex items-center gap-2">
         <i class="fab fa-{{ $post->platform }}"></i>
         <span class="capitalize">{{ $post->platform }}</span>
        </div>
       </td>
       <td>
        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300 {{ $post->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($post->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : ($post->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300')) }}">
         {{ ucfirst($post->status) }}
        </span>
       </td>
       <td>{{ $post->scheduled_at ? $post->scheduled_at->format('M d, Y H:i') : '—' }}</td>
       <td>
        <div class="flex items-center gap-2">
         <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
          <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ ($post->quality_score ?? 0) * 10 }}%"></div>
         </div>
         <span class="text-xs text-gray-500">{{ $post->quality_score ?? 0 }}/10</span>
        </div>
       </td>
       <td>
        <div class="flex items-center gap-2">
         <a href="{{ route('social.posts.show', $post) }}" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-1 text-sm font-medium transition-colors text-gray-700 dark:text-gray-200" title="View">
          <i class="fas fa-eye"></i>
         </a>
         <a href="{{ route('social.posts.edit', $post) }}" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-1 text-sm font-medium transition-colors text-gray-700 dark:text-gray-200" title="Edit">
          <i class="fas fa-edit"></i>
         </a>
         <form method="POST" action="{{ route('social.posts.destroy', $post) }}" class="inline">
          @csrf @method('DELETE')
          <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 inline-flex items-center gap-1 text-sm font-medium transition-colors" title="Delete" onclick="return confirm('Are you sure?')">
           <i class="fas fa-trash"></i>
          </button>
         </form>
        </div>
       </td>
      </tr>
     @empty
      <tr>
       <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <i class="fas fa-inbox text-4xl mb-4 block"></i>
        No posts found. <a href="{{ route('social.posts.create') }}" class="text-indigo-600 hover:text-indigo-700">Create your first post</a>.
       </td>
      </tr>
     @endforelse
    </tbody>
   </table></div>
  </div>
  @if($posts->hasPages())
   <div class="p-4 border-t border-gray-200 dark:border-gray-700">
    {{ $posts->links() }}
   </div>
  @endif
 </div>
@endsection
