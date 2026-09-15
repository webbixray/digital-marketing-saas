@extends("layouts.unified")

@section('title', 'Posts')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Social Posts</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage and schedule your social media content.</p>
        </div>
        <a href="{{ route('social.posts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Post
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <select name="platform" class="form-input">
                        <option value="">All Platforms</option>
                        @foreach($platforms as $key => $label)
                            <option value="{{ $key }}" {{ request('platform') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" class="form-input" placeholder="Search content..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Posts Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
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
                                <span class="badge {{ $post->status === 'published' ? 'badge-success' : ($post->status === 'failed' ? 'badge-danger' : ($post->status === 'scheduled' ? 'badge-warning' : 'badge-info')) }}">
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
                                    <a href="{{ route('social.posts.show', $post) }}" class="btn btn-sm btn-secondary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('social.posts.edit', $post) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('social.posts.destroy', $post) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
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
            </table>
        </div>
        @if($posts->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection
