@extends("layouts.unified")

@section('title', $modelVersion->name . ' v' . $modelVersion->version)

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('ai-training.index') }}" class="hover:text-indigo-600">AI Training</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span>{{ $modelVersion->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $modelVersion->name }}
                    <span class="text-lg font-normal text-gray-500">v{{ $modelVersion->version }}</span>
                    @if($modelVersion->is_active)
                        <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full">Active</span>
                    @endif
                </h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Base: {{ $modelVersion->base_model }} | Status:
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                        {{ $modelVersion->status === 'ready' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                        {{ $modelVersion->status === 'training' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                        {{ $modelVersion->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                        {{ $modelVersion->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                    ">{{ ucfirst($modelVersion->status) }}</span>
                </p>
            </div>
            <div class="flex gap-3">
                @if($modelVersion->status === 'ready' && !$modelVersion->is_active)
                    <form method="POST" action="{{ route('ai-training.activate', $modelVersion->id) }}">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors inline-flex items-center gap-2">
                            <i class="fas fa-check-circle"></i> Activate
                        </button>
                    </form>
                @endif
                @if($modelVersion->status === 'ready')
                    <a href="{{ route('ai-training.evaluate', $modelVersion->id) }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors inline-flex items-center gap-2">
                        <i class="fas fa-chart-bar"></i> Evaluate
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Metrics Overview -->
    @if($modelVersion->metrics)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Accuracy</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format(($modelVersion->metrics['accuracy'] ?? 0) * 100, 1) }}%</p>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">F1 Score</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($modelVersion->metrics['f1_score'] ?? 0, 4) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Loss</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($modelVersion->metrics['loss'] ?? 0, 4) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Perplexity</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($modelVersion->metrics['perplexity'] ?? 0, 2) }}</p>
            </div>
        </div>
    @endif

    <!-- Training Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Model Info -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Model Details</h3>
            </div>
            <div class="p-6 space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Base Model</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $modelVersion->base_model }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Version</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">v{{ $modelVersion->version }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Status</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($modelVersion->status) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Trained At</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $modelVersion->trained_at?->toDateTimeString() ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Training Data Hash</span>
                    <span class="text-sm font-mono text-gray-900 dark:text-white">{{ Str::limit($modelVersion->training_data_hash, 16) ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">File Path</span>
                    <span class="text-sm font-mono text-gray-900 dark:text-white">{{ Str::limit($modelVersion->file_path, 30) ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Training Metrics Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Training Metrics</h3>
            </div>
            <div class="p-6">
                @if($modelVersion->metrics)
                    <div class="space-y-4">
                        @foreach(['accuracy', 'f1_score', 'loss', 'perplexity'] as $metric)
                            @if(isset($modelVersion->metrics[$metric]))
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $metric)) }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ is_float($modelVersion->metrics[$metric]) ? number_format($modelVersion->metrics[$metric], 4) : $modelVersion->metrics[$metric] }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, ($modelVersion->metrics[$metric] * 100)) }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No metrics available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Evaluation Results -->
    @if($evaluationResult)
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Latest Evaluation</h3>
                <p class="text-sm text-gray-500 mt-1">Tested against: {{ $evaluationResult['test_dataset_name'] }}</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach($evaluationResult['metrics'] as $key => $value)
                        @if(is_numeric($value))
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ is_float($value) ? number_format($value, 4) : $value }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Training Jobs -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Training Jobs</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Dataset</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Started</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modelVersion->trainingJobs as $job)
                        <tr>
                            <td class="text-sm font-mono text-gray-600 dark:text-gray-400">#{{ $job->id }}</td>
                            <td class="text-sm text-gray-900 dark:text-white">{{ $job->dataset->name ?? 'N/A' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $job->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $job->status === 'running' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $job->status === 'queued' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                    {{ $job->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                    {{ $job->status === 'cancelled' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                ">{{ ucfirst($job->status) }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $job->progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $job->progress }}%</span>
                                </div>
                            </td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $job->started_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-sm text-gray-600 dark:text-gray-400">{{ $job->completed_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">No training jobs for this model.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
