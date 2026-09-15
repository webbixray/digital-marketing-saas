@extends("layouts.unified")

@section('title', 'Content Library')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Content Library</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your content templates and assets.</p>
        </div>
        <a href="{{ route('content.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Content
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $content)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <i class="fas fa-file-alt text-blue-600 dark:text-blue-400"></i>
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $content->title ?? $content->name }}</span>
                                </div>
                            </td>
                            <td class="capitalize text-gray-500 dark:text-gray-400">{{ $content->type ?? 'post' }}</td>
                            <td>
                                <span class="badge {{ ($content->status ?? 'draft') === 'published' ? 'badge-success' : 'badge-warning' }}">
                                    {{ ucfirst($content->status ?? 'draft') }}
                                </span>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $content->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('content.show', $content) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('content.edit', $content) }}" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('content.destroy', $content) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-folder-open text-4xl mb-4 block"></i>
                                No content found. <a href="{{ route('content.create') }}" class="text-indigo-600 hover:text-indigo-700">Create your first content</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assets->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $assets->links() }}
            </div>
        @endif
    </div>
@endsection
