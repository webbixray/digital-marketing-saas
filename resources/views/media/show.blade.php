@extends('layouts.unified')
@section('title', $asset->name)

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
                <div class="col-span-12 md:col-span-4">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Details</h3></div>
                        <div class="p-6">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <tr><td><strong>Name</strong></td><td>{{ $asset->name }}</td></tr>
                                <tr><td><strong>Type</strong></td><td><span class="badge badge-{{ $asset->file_type === 'image' ? 'success' : ($asset->file_type === 'video' ? 'info' : 'secondary') }}">{{ $asset->file_type }}</span></td></tr>
                                <tr><td><strong>Size</strong></td><td>{{ $asset->human_size }}</td></tr>
                                <tr><td><strong>Dimensions</strong></td><td>{{ $asset->width ?? '?' }} x {{ $asset->height ?? '?' }}</td></tr>
                                <tr><td><strong>MIME</strong></td><td>{{ $asset->mime_type }}</td></tr>
                                <tr><td><strong>Folder</strong></td><td>{{ $asset->folder ?? 'Uncategorized' }}</td></tr>
                                <tr><td><strong>Uploaded</strong></td><td>{{ $asset->created_at->format('M d, Y H:i') }}</td></tr>
                                <tr><td><strong>Usage</strong></td><td>{{ $asset->usage_count }} times</td></tr>
                            </table>
                            <hr>
                            <a href="{{ route('media.download', $asset) }}" class="btn btn-primary btn-block"><i class="fas fa-download"></i> Download</a>
                            <a href="{{ route('media.duplicate', $asset) }}" class="btn btn-secondary btn-block"><i class="fas fa-copy"></i> Duplicate</a>
                            <form action="{{ route('media.destroy', $asset) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete this file permanently?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

