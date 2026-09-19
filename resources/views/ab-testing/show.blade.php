@extends("layouts.unified")
@section('title', $test->name)

@section('content')

<x-flash-messages />

<!-- Breadcrumb -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white m-0">{{ $test->name }}</h1>
    </div>
    <div class="flex items-center justify-start sm:justify-end">
        <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400">
            <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('ab-testing.index') }}" class="hover:text-indigo-600">A/B Testing</a></li>
            <li>/</li>
            <li class="text-gray-900 dark:text-white font-medium">{{ $test->name }}</li>
        </ol>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Test Results</h3>
                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $test->status === 'running' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($test->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300') }}">{{ ucfirst($test->status) }}</span>
            </div>
            <div class="p-6">
                @if($test->hypothesis)
                <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4">
                    <strong>Hypothesis:</strong> {{ $test->hypothesis }}
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Variant A (Control)</h3>
                        </div>
                        <div class="p-6">
                            <p>{{ $test->variant_a_content }}</p>
                            <hr class="my-4 border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $test->variant_a_impressions }}</h4>
                                    <small class="text-gray-500 dark:text-gray-400">Impressions</small>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $test->variant_a_engagement }}</h4>
                                    <small class="text-gray-500 dark:text-gray-400">Engagement</small>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $test->variant_a_clicks }}</h4>
                                    <small class="text-gray-500 dark:text-gray-400">Clicks</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Variant B (Treatment)</h3>
                        </div>
                        <div class="p-6">
                            <p>{{ $test->variant_b_content }}</p>
                            <hr class="my-4 border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $test->variant_b_impressions }}</h4>
                                    <small class="text-gray-500 dark:text-gray-400">Impressions</small>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $test->variant_b_engagement }}</h4>
                                    <small class="text-gray-500 dark:text-gray-400">Engagement</small>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $test->variant_b_clicks }}</h4>
                                    <small class="text-gray-500 dark:text-gray-400">Clicks</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($test->winner)
                <div class="mt-4 px-4 py-3 rounded-lg {{ $test->winner === 'inconclusive' ? 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800' }}">
                    <strong>Winner:</strong> {{ ucfirst($test->winner) }} ({{ $test->confidence }}% confidence)
                </div>
                @endif
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
                @if($test->status === 'draft')
                <form action="{{ route('ab-testing.start', $test) }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-play mr-2"></i>Start Test</button>
                </form>
                @endif
                @if($test->status === 'running')
                <form action="{{ route('ab-testing.pause', $test) }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-pause mr-2"></i>Pause</button>
                </form>
                @endif
                @if(in_array($test->status, ['running', 'paused']))
                <form action="{{ route('ab-testing.complete', $test) }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors"><i class="fas fa-check mr-2"></i>Complete</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Details</h3>
            </div>
            <div class="p-6 space-y-2">
                <p><strong>Platform:</strong> {{ ucfirst($test->platform) }}</p>
                <p><strong>Type:</strong> {{ ucfirst($test->type) }}</p>
                <p><strong>Sample Size:</strong> {{ $test->sample_size }} per variant</p>
                <p><strong>Account:</strong> {{ $test->socialAccount?->platform_username }}</p>
                <p><strong>Created:</strong> {{ $test->created_at->toDateString() }}</p>
                @if($test->started_at)
                <p><strong>Started:</strong> {{ $test->started_at->toDateString() }}</p>
                @endif
                @if($test->ended_at)
                <p><strong>Ended:</strong> {{ $test->ended_at->toDateString() }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
