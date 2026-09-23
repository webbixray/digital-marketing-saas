@extends("layouts.unified")

@section('title', 'Evaluate Model')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('ai-training.index') }}" class="hover:text-indigo-600">AI Training</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <a href="{{ route('ai-training.show', $modelVersion->id) }}" class="hover:text-indigo-600">{{ $modelVersion->name }}</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span>Evaluation</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Evaluation Results</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Model: {{ $modelVersion->name }} v{{ $modelVersion->version }}</p>
    </div>

    @if(empty($evaluationResults))
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-12 text-center">
            <i class="fas fa-chart-bar text-4xl text-gray-400 mb-4 block"></i>
            <p class="text-gray-500 dark:text-gray-400">No evaluation datasets available.</p>
            <a href="{{ route('ai-training.datasets') }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-sm underline mt-2 inline-block">
                Upload a dataset to evaluate against
            </a>
        </div>
    @else
        @foreach($evaluationResults as $result)
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Test Dataset: {{ $result['test_dataset_name'] }}</h3>
                        <p class="text-sm text-gray-500">Evaluated: {{ $result['evaluated_at'] }}</p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        @foreach($result['metrics'] as $key => $value)
                            @if(is_numeric($value))
                                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ is_float($value) ? number_format($value, 4) : $value }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if(!empty($result['sample_predictions']))
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Sample Predictions</h4>
                            <div class="space-y-2">
                                @foreach($result['sample_predictions'] as $index => $prediction)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Sample {{ $index + 1 }}: {{ $prediction }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
@endsection
