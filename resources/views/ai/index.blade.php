@extends("layouts.unified")

@section('title', 'AI Content')

@section('content')
    <x-flash-messages />
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">AI Content</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Generate content with artificial intelligence.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card">
            <p class="stat-value">{{ $aiStats['total_generations'] ?? 0 }}</p>
            <p class="stat-label">Total Generations</p>
        </div>
        <div class="stat-card">
            <p class="stat-value">{{ $aiStats['successful'] ?? 0 }}</p>
            <p class="stat-label">Successful</p>
        </div>
        <div class="stat-card">
            <p class="stat-value">${{ number_format($aiStats['total_cost'] ?? 0, 2) }}</p>
            <p class="stat-label">Total Cost</p>
        </div>
    </div>

    <!-- Generation Form -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Generate Content</h3>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('ai.generate') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="ai-content-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content Type</label>
                        <select name="type" id="ai-content-type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="caption">Social Media Caption</option>
                            <option value="hashtags">Hashtags</option>
                            <option value="blog">Blog Post</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topic / Prompt</label>
                        <textarea name="prompt" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Describe what you want to generate..."></textarea>
                    </div>
                    <div>
                        <label for="ai-tone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tone</label>
                        <select name="tone" id="ai-tone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="friendly">Friendly</option>
                            <option value="formal">Formal</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                        <i class="fas fa-sparkles"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Recent Generations</h3>
        </div>
        <div class="overflow-x-auto">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Tokens</th>
                        <th>Cost</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs ?? [] as $log)
                        <tr>
                            <td class="capitalize">{{ $log->action ?? 'content' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $log->status === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td>{{ $log->total_tokens ?? 0 }}</td>
                            <td>${{ number_format($log->cost_usd ?? 0, 4) }}</td>
                            <td>{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">No AI generations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection