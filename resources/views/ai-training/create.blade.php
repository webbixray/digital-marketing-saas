@extends("layouts.unified")

@section('title', 'Start New Training')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('ai-training.index') }}" class="hover:text-indigo-600">AI Training</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span>New Training</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Start New Training</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Configure and launch a new model training job.</p>
    </div>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('ai-training.store') }}" class="space-y-6">
            @csrf

            <!-- Model Configuration -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Model Configuration</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="model_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model Name</label>
                        <input type="text" id="model_name" name="model_name" value="{{ old('model_name') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="My Custom Content Model">
                        @error('model_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="base_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Base Model</label>
                        <select id="base_model" name="base_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="llama-3">Llama 3</option>
                            <option value="mistral">Mistral</option>
                            <option value="phi-3">Phi-3</option>
                            <option value="gemma">Gemma</option>
                            <option value="custom">Custom</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">The base model to fine-tune.</p>
                    </div>

                    <div>
                        <label for="dataset_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Training Dataset</label>
                        <select id="dataset_id" name="dataset_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select a dataset...</option>
                            @forelse($datasets as $dataset)
                                <option value="{{ $dataset->id }}" {{ old('dataset_id') == $dataset->id ? 'selected' : '' }}>
                                    {{ $dataset->name }} ({{ number_format($dataset->row_count) }} rows)
                                </option>
                            @empty
                                <option value="" disabled>No ready datasets available. Upload one first.</option>
                            @endforelse
                        </select>
                        @error('dataset_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @if($datasets->isEmpty())
                            <p class="mt-1 text-xs text-amber-600"><a href="{{ route('ai-training.datasets') }}" class="underline">Upload a dataset first</a> to start training.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Hyperparameters -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Hyperparameters</h3>
                    <p class="text-sm text-gray-500 mt-1">Fine-tune training parameters for optimal results.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="epochs" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Epochs</label>
                            <input type="number" id="epochs" name="epochs" value="{{ old('epochs', 3) }}" min="1" max="100"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Number of full passes through the dataset.</p>
                        </div>
                        <div>
                            <label for="learning_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Learning Rate</label>
                            <input type="number" id="learning_rate" name="learning_rate" value="{{ old('learning_rate', 0.0001) }}" min="0" max="1" step="0.00001"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Controls how quickly the model adapts.</p>
                        </div>
                        <div>
                            <label for="batch_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Batch Size</label>
                            <input type="number" id="batch_size" name="batch_size" value="{{ old('batch_size', 16) }}" min="1" max="256"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500">Samples processed before updating weights.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('ai-training.index') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2">
                    <i class="fas fa-rocket"></i> Start Training
                </button>
            </div>
        </form>
    </div>
@endsection
