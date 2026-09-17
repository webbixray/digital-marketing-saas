@extends('layouts.unified')
@section('title', $asset->name)

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-4">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Details</h3></div>
                        <div class="p-6">
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <tr><td><strong>Name</strong></td><td>{{ $asset->name }}</td></tr>
                                <tr><td><strong>Type</strong></td><td><span class="px-2 py-1 text-xs font-medium rounded-full { $asset->file_type === 'image' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($asset->file_type === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }">{{ $asset->file_type }}</span></td></tr>
                                <tr><td><strong>Size</strong></td><td>{{ $asset->human_size }}</td></tr>
                                <tr><td><strong>Dimensions</strong></td><td>{{ $asset->width ?? '?' }} x {{ $asset->height ?? '?' }}</td></tr>
                                <tr><td><strong>MIME</strong></td><td>{{ $asset->mime_type }}</td></tr>
                                <tr><td><strong>Folder</strong></td><td>{{ $asset->folder ?? 'Uncategorized' }}</td></tr>
                                <tr><td><strong>Uploaded</strong></td><td>{{ $asset->created_at->format('M d, Y H:i') }}</td></tr>
                                <tr><td><strong>Usage</strong></td><td>{{ $asset->usage_count }} times</td></tr>
                            </table></div>
                            <hr>
                            <a href="{{ route('media.download', $asset) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors w-full justify-center"><i class="fas fa-download"></i> Download</a>
                            <a href="{{ route('media.duplicate', $asset) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors w-full justify-center"><i class="fas fa-copy"></i> Duplicate</a>
                            <form action="{{ route('media.destroy', $asset) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete this file permanently?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors w-full justify-center"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
</div>
</div>
@endsection

