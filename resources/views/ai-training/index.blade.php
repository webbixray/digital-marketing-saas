@extends("layouts.unified")

@section('title', 'AI Model Training')

@section('content')
    <x-flash-messages />
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">AI Model Training</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your fine-tuned AI models and training jobs.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('ai-training.datasets') }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors inline-flex items-center gap-2">
                <i class="fas fa-database"></i> Datasets
            </a>
            <a href="{{ route('ai-training.jobs') }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors inline-flex items-center gap-2">
                <i class="fas fa-tasks"></i> Jobs
            </a>
            <a href="{{ route('ai-training.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> New Training
            </a>
        </div>
    </div>

    <!-- Active Model -->
    @if($activeModel)
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-md p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">Active Model</p>
                    <h3 class="text-xl font-bold mt-1">{{ $activeModel->name }} <span class="text-sm opacity-70">v{{ $activeModel->version }}</span></h3>
                    <p class="text-sm opacity-80 mt-1">Base: {{ $activeModel->base_model }} | Trained: {{ $activeModel->trained_at?->diffForHumans() ?? 'N/A' }}</p>
                </div>
                @if($activeModel->metrics)
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold">{{ number_format(($activeModel->metrics['accuracy'] ?? 0) * 100, 1) }}%</p>
                            <p class="text-xs opacity-80">Accuracy</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold">{{ $activeModel->metrics['f1_score'] ?? 0 }}</p>
                            <p class="text-xs opacity-80">F1 Score</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold">{{ number_format($activeModel->metrics['loss'] ?? 0, 4) }}</p>
                            <p class="text-xs opacity-80">Loss</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Model Versions -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">Model Versions</h3>
            <span class="text-sm text-gray-500">{{ $modelVersions->count() }} versions</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Model</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Base Model</th>
                        <th>Accuracy</th>
                        <th>Trained</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modelVersions as $model)
                        <tr>
                            <td>
                                <a href="{{ route('ai-training.show', $model->id) }}" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                    {{ $model->name }}
                                </a>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">v{{ $model->version }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $model->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $model->status === 'training' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                    {{ $model->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                    {{ $model->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                ">
                                    {{ ucfirst($model->status) }}
                                </span>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $model->base_model }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">
                                {{ isset($model->metrics['accuracy']) ? number_format($model->metrics['accuracy'] * 100, 1) . '%' : 'N/A' }}
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $model->trained_at?->diffForHumans() ?? '—' }}</td>
                            <td>
                                @if($model->is_active)
                                    <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                @elseif($model->status === 'ready')
                                    <form method="POST" action="{{ route('ai-training.activate', $model->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Activate</button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-sm">
                                <a href="{{ route('ai-training.show', $model->id) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 mr-3"><i class="fas fa-eye"></i></a>
                                @if($model->status === 'ready')
                                    <a href="{{ route('ai-training.evaluate', $model->id) }}" class="text-purple-600 hover:text-purple-800 dark:text-purple-400"><i class="fas fa-chart-bar"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-brain text-4xl mb-3 block"></i>
                                No trained models yet. Start by uploading a dataset and creating a training job.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Training Jobs -->
    @if($latestJobs->isNotEmpty())
        <div class="mt-6 bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Active Training Jobs</h3>
            </div>
            <div class="p-6 space-y-4">
                @foreach($latestJobs as $job)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $job->modelVersion->name }} v{{ $job->modelVersion->version }}</p>
                            <p class="text-sm text-gray-500">Dataset: {{ $job->dataset->name }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-48 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $job->progress }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-400 w-12">{{ $job->progress }}%</span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $job->status === 'running' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }}
                            ">{{ ucfirst($job->status) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
