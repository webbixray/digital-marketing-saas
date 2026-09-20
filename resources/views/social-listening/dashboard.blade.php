@extends("layouts.unified")

@section('title', 'Social Listening')

@section('content')
    <x-flash-messages />

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Social Listening</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Monitor brand mentions and track sentiment across social platforms.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('social-listening.refresh') }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
            <a href="{{ route('social-listening.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-plus"></i> Add Keyword
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Keywords</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_keywords'] ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-search text-indigo-600 dark:text-indigo-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Mentions</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_mentions'] ?? 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-comments text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Positive Sentiment</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['sentiment_percentages']['positive'] ?? 0 }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-smile text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Negative Sentiment</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['sentiment_percentages']['negative'] ?? 0 }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-frown text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sentiment Chart -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Sentiment Breakdown</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">Overall Score: <span class="font-semibold {{ ($stats['overall_sentiment_score'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $stats['overall_sentiment_score'] ?? 0 }}</span></span>
            </div>
            <div class="p-6">
                <!-- Sentiment Bar -->
                <div class="mb-6">
                    <div class="flex h-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                        @php
                            $posWidth = $stats['sentiment_percentages']['positive'] ?? 0;
                            $negWidth = $stats['sentiment_percentages']['negative'] ?? 0;
                            $neuWidth = $stats['sentiment_percentages']['neutral'] ?? 0;
                        @endphp
                        <div class="bg-green-500" style="width: {{ $posWidth }}%"></div>
                        <div class="bg-gray-400" style="width: {{ $neuWidth }}%"></div>
                        <div class="bg-red-500" style="width: {{ $negWidth }}%"></div>
                    </div>
                    <div class="flex justify-between mt-2 text-sm">
                        <span class="text-green-600 dark:text-green-400"><i class="fas fa-circle text-xs"></i> Positive ({{ $stats['total_positive'] ?? 0 }})</span>
                        <span class="text-gray-500 dark:text-gray-400"><i class="fas fa-circle text-xs"></i> Neutral ({{ $stats['total_neutral'] ?? 0 }})</span>
                        <span class="text-red-600 dark:text-red-400"><i class="fas fa-circle text-xs"></i> Negative ({{ $stats['total_negative'] ?? 0 }})</span>
                    </div>
                </div>

                <!-- Platform Breakdown -->
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Platform Breakdown</h4>
                <div class="space-y-3">
                    @forelse($stats['platform_breakdown'] ?? [] as $platform => $data)
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fab fa-{{ $platform }} text-gray-600 dark:text-gray-300"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $platform }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($data['mentions'] ?? 0) }} mentions</span>
                                </div>
                                <div class="flex h-2 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                                    @php
                                        $total = ($data['positive'] ?? 0) + ($data['negative'] ?? 0) + ($data['neutral'] ?? 0);
                                        $posP = $total > 0 ? (($data['positive'] ?? 0) / $total) * 100 : 0;
                                        $negP = $total > 0 ? (($data['negative'] ?? 0) / $total) * 100 : 0;
                                    @endphp
                                    <div class="bg-green-500" style="width: {{ $posP }}%"></div>
                                    <div class="bg-gray-400" style="width: {{ 100 - $posP - $negP }}%"></div>
                                    <div class="bg-red-500" style="width: {{ $negP }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No platform data yet. Add keywords to start monitoring.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Keyword List -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Keywords</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $keywords->count() }} total</span>
            </div>
            <div class="p-6">
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($keywords as $keyword)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $keyword->keyword }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $keyword->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300' }}">
                                        {{ $keyword->is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span><i class="fab fa-{{ $keyword->platform }}"></i> {{ ucfirst($keyword->platform) }}</span>
                                    <span>{{ number_format($keyword->match_count) }} mentions</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <form action="{{ route('social-listening.toggle', $keyword) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500" title="{{ $keyword->is_active ? 'Pause' : 'Activate' }}">
                                        <i class="fas fa-{{ $keyword->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                </form>
                                <a href="{{ route('social-listening.edit', $keyword) }}" class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('social-listening.destroy', $keyword) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500" title="Delete" onclick="return confirm('Remove this keyword?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                            No keywords yet.<br>
                            <a href="{{ route('social-listening.create') }}" class="text-indigo-600 hover:text-indigo-700">Add your first keyword</a>
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Mentions Feed -->
    <div class="mt-6 bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($mentionFeed as $item)
                    <div class="flex items-start gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                            @switch($item['sentiment_score'] ?? 0)
                                @case($item['sentiment_score'] > 0) bg-green-100 dark:bg-green-900/30 @break
                                @case($item['sentiment_score'] < 0) bg-red-100 dark:bg-red-900/30 @break
                                @default bg-gray-200 dark:bg-gray-600
                            @endswitch">
                            <i class="fas fa-{{ ($item['sentiment_score'] ?? 0) > 0 ? 'smile text-green-600' : (($item['sentiment_score'] ?? 0) < 0 ? 'frown text-red-600' : 'meh text-gray-500') }}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item['keyword'] }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">{{ ucfirst($item['platform']) }}</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ number_format($item['match_count']) }} mentions tracked</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-medium {{ ($item['sentiment_score'] ?? 0) > 0 ? 'text-green-600' : (($item['sentiment_score'] ?? 0) < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                {{ ($item['sentiment_score'] ?? 0) > 0 ? '+' : '' }}{{ $item['sentiment_score'] ?? 0 }}%
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $item['last_checked'] ?? 'Never' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent activity. Keywords will appear here once monitoring begins.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
