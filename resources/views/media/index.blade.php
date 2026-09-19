@extends('layouts.unified')
@section('title', 'Media Library')

@section('styles')
<style>
  .media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    padding: 20px;
  }
  .media-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
  }
  .media-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
  }
  .media-card.selected {
    box-shadow: 0 0 0 3px #007bff;
  }
  .media-preview {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    position: relative;
  }
  .media-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
  }
  .media-preview i {
    font-size: 48px;
    color: #dee2e6;
  }
  .media-info {
    padding: 12px;
  }
  .media-name {
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .media-meta {
    font-size: 11px;
    color: #6c757d;
    margin-top: 4px;
  }
  .media-actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: none;
  }
  .media-card:hover .media-actions {
    display: flex;
    gap: 4px;
  }
  .media-actions button {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    border: none;
    background: rgba(0,0,0,0.6);
    color: white;
    cursor: pointer;
    font-size: 12px;
  }
  .media-actions button:hover {
    background: rgba(0,0,0,0.8);
  }
  .folder-sidebar {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
  }
  .folder-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    color: #495057;
    transition: all 0.15s;
  }
  .folder-item:hover {
    background: #e9ecef;
  }
  .folder-item.active {
    background: #007bff;
    color: white;
  }
  .upload-zone {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    transition: all 0.2s;
    cursor: pointer;
  }
  .upload-zone:hover {
    border-color: #007bff;
    background: #f8f9fa;
  }
  .upload-zone i {
    font-size: 48px;
    color: #dee2e6;
    margin-bottom: 16px;
  }
  .upload-zone h4 {
    margin-bottom: 8px;
    color: #495057;
  }
  .upload-zone p {
    font-size: 13px;
    color: #6c757d;
  }
</style>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">

  <!-- Stats Row -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div>
      <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
        <span class="w-12 h-12 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center"><i class="fas fa-images"></i></span>
        <div class="flex-1">
          <span class="text-xs text-gray-500 dark:text-gray-400">Total Files</span>
          <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $assets->total() }}</span>
        </div>
      </div>
    </div>
    <div>
      <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
        <span class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center"><i class="fas fa-image"></i></span>
        <div class="flex-1">
          <span class="text-xs text-gray-500 dark:text-gray-400">Images</span>
          <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $assets->where('file_type', 'image')->count() }}</span>
        </div>
      </div>
    </div>
    <div>
      <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
        <span class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center"><i class="fas fa-video"></i></span>
        <div class="flex-1">
          <span class="text-xs text-gray-500 dark:text-gray-400">Videos</span>
          <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $assets->where('file_type', 'video')->count() }}</span>
        </div>
      </div>
    </div>
    <div>
      <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 flex items-center gap-4">
        <span class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center"><i class="fas fa-hdd"></i></span>
        <div class="flex-1">
          <span class="text-xs text-gray-500 dark:text-gray-400">Storage Used</span>
          <span class="text-lg font-bold text-gray-900 dark:text-white">{{ round($totalSize / 1048576, 1) }} MB</span>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Folders Sidebar -->
    <div class="md:col-span-1">
      <div class="folder-sidebar">
        <h5><i class="fas fa-folder-open"></i> Folders</h5>
        <hr>
        <div class="folder-item {{ request('folder') ? '' : 'active' }}" onclick="window.location='{{ route('media.index') }}'">
          <i class="fas fa-folder"></i> All Files
        </div>
        @foreach($folders as $folder)
          @if($folder)
          <div class="folder-item {{ request('folder') === $folder ? 'active' : '' }}" onclick="window.location='{{ route('media.index', ['folder' => $folder]) }}'">
            <i class="fas fa-folder"></i> {{ $folder }}
          </div>
          @endif
        @endforeach
      </div>
    </div>

    <!-- Main Content -->
    <div class="md:col-span-3">
      <!-- Upload Zone -->
      <div class="upload-zone mb-3" onclick="document.getElementById('fileInput').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <h4>Upload Files</h4>
        <p>Drag & drop files here or click to browse. Max 10MB per file.</p>
      </div>

      <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-semibold text-gray-900 dark:text-white">Files</h3>
          <div class="flex items-center gap-2">
            <form action="{{ route('media.index') }}" method="GET" class="inline-flex">
              <div class="flex gap-2" style="width: 250px;">
                <input type="text" name="search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Search files..." value="{{ request('search') }}">
                <div class="">
                  <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-search"></i></button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="p-6">
          <form id="uploadForm" action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data" style="display:none">
            @csrf
            <input type="file" id="fileInput" name="files[]" multiple accept="image/*,video/*,.pdf,.doc,.docx" onchange="this.form.submit()">
            <input type="hidden" name="folder" id="uploadFolder" value="uncategorized">
          </form>

          @if($assets->isEmpty())
            <div class="text-center py-5">
              <i class="fas fa-images fa-3x text-gray-500 dark:text-gray-400 mb-3"></i>
              <h4 class="text-gray-500 dark:text-gray-400">No files yet</h4>
              <p class="text-gray-500 dark:text-gray-400">Upload your first file to get started</p>
            </div>
          @else
            <div class="media-grid">
              @foreach($assets as $asset)
              <div class="media-card" onclick="window.location='{{ route('media.show', $asset) }}'">
                <div class="media-preview">
                  @if($asset->file_type === 'image')
                    <img loading="lazy" src="{{ $asset->thumbnail_url }}" alt="{{ $asset->alt_text ?? $asset->name }}">
                  @elseif($asset->file_type === 'video')
                    <i class="fas fa-video"></i>
                  @else
                    <i class="fas fa-file-alt"></i>
                  @endif
                </div>
                <div class="media-info">
                  <div class="media-name" title="{{ $asset->name }}">{{ $asset->name }}</div>
                  <div class="media-meta">
                    {{ $asset->human_size }} &middot; {{ $asset->created_at->diffForHumans() }}
                  </div>
                </div>
                <div class="media-actions">
                  <a href="{{ route('media.download', $asset) }}" title="Download" onclick="event.stopPropagation()"><i class="fas fa-download"></i></a>
                  <a href="{{ route('media.duplicate', $asset) }}" title="Duplicate" onclick="event.stopPropagation()"><i class="fas fa-copy"></i></a>
                  <form action="{{ route('media.destroy', $asset) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                    @csrf @method('DELETE')
                    <button type="submit" title="Delete" onclick="event.stopPropagation()"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </div>
              @endforeach
            </div>
          @endif
        </div>
        @if($assets->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
          {{ $assets->appends(request()->query())->links() }}
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
