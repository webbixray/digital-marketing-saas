@extends("layouts.unified")

@section('title', 'Training Datasets')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('ai-training.index') }}" class="hover:text-indigo-600">AI Training</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span>Datasets</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Training Datasets</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Upload and manage datasets for model training.</p>
            </div>
            <button type="button" onclick="document.getElementById('upload-modal').classList.remove('hidden')"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2">
                <i class="fas fa-upload"></i> Upload Dataset
            </button>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900 dark:text-white">Upload Dataset</h3>
                <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('ai-training.datasets.upload') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dataset Name</label>
                    <input type="text" id="name" name="name" required value="{{ old('name') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="My Training Data">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea id="description" name="description" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Brief description of this dataset...">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label for="dataset_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File</label>
                    <input type="file" id="dataset_file" name="dataset_file" required accept=".csv,.json,.jsonl,.xlsx"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">Supported: CSV, JSON, JSONL, XLSX. Max 100MB.</p>
                    @error('dataset_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Datasets List -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Rows</th>
                        <th>Columns</th>
                        <th>File Hash</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datasets as $dataset)
                        <tr>
                            <td>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $dataset->name }}</p>
                                    @if($dataset->description)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($dataset->description, 60) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $dataset->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $dataset->status === 'processing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $dataset->status === 'uploading' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                    {{ $dataset->status === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                ">{{ ucfirst($dataset->status) }}</span>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($dataset->row_count) }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $dataset->column_count }}</td>
                            <td class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ Str::limit($dataset->file_hash, 12) ?? '—' }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $dataset->created_at->diffForHumans() }}</td>
                            <td>
                                @if($dataset->status === 'ready')
                                    <a href="{{ route('ai-training.create') }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-sm" title="Use for training">
                                        <i class="fas fa-brain"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-database text-4xl mb-3 block"></i>
                                No datasets uploaded yet. Click "Upload Dataset" to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
