<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->brand_name ?? $agency->name }} — Client Portal</title>
    <meta name="theme-color" content="{{ $settings->brand_color }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/unified.css'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100 font-inter">
    <div class="flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-3">
                        @if($settings->logo_url ?? $agency->logo)
                            <img src="{{ $settings->logo_url ?? $agency->logo }}" alt="{{ $settings->brand_name ?? $agency->name }}" class="h-8 w-8 object-contain rounded-lg">
                        @else
                            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-building text-white text-sm"></i>
                            </div>
                        @endif
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $settings->brand_name ?? $agency->name }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $client->name }}</span>
                        <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-indigo-600 dark:text-indigo-400 text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Welcome Section -->
        @if($settings->welcome_message)
        <div class="bg-indigo-600 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 class="text-2xl font-bold mb-2">{{ $settings->welcome_message }}</h1>
                <p class="opacity-90">Welcome to your client portal. Here you can view campaigns, approve posts, and track performance.</p>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Posts</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_posts'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-pen-nib text-indigo-600 dark:text-indigo-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Published</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['published_posts'] }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Engagement Rate</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['engagement_rate'], 1) }}%</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-chart-line text-yellow-600 dark:text-yellow-400 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div x-data="{ activeTab: 'campaigns' }">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="flex gap-8">
                        <button @click="activeTab = 'campaigns'" :class="activeTab === 'campaigns' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-4 border-b-2 font-medium text-sm transition-colors">
                            Campaigns
                        </button>
                        <button @click="activeTab = 'pending'" :class="activeTab === 'pending' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-4 border-b-2 font-medium text-sm transition-colors">
                            Pending Approvals
                        </button>
                        @if($settings->show_invoices)
                        <button @click="activeTab = 'invoices'" :class="activeTab === 'invoices' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-4 border-b-2 font-medium text-sm transition-colors">
                            Invoices
                        </button>
                        @endif
                    </nav>
                </div>

                <!-- Campaigns Tab -->
                <div x-show="activeTab === 'campaigns'">
                    @forelse($campaigns as $campaign)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-4">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $campaign->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $campaign->description ?? 'No description' }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($campaign->status) }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span><i class="fas fa-pen-nib mr-1"></i>{{ $campaign->social_posts_count }} posts</span>
                                <span><i class="fas fa-calendar mr-1"></i>{{ $campaign->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-12 text-center">
                        <i class="fas fa-bullhorn text-4xl text-gray-300 dark:text-gray-600 mb-4 block"></i>
                        <p class="text-gray-500 dark:text-gray-400">No campaigns yet</p>
                    </div>
                    @endforelse
                    {{ $campaigns->links() }}
                </div>

                <!-- Pending Approvals Tab -->
                <div x-show="activeTab === 'pending'">
                    @php
                        $pendingPosts = $client->socialPosts()->where('status', 'pending_approval')->get();
                    @endphp
                    @forelse($pendingPosts as $post)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-4">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fab fa-{{ $post->platform }} text-gray-500"></i>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($post->platform) }}</span>
                                    </div>
                                    <p class="text-gray-900 dark:text-white">{{ $post->content }}</p>
                                    @if($post->media_url)
                                    <div class="mt-2">
                                        <img src="{{ $post->media_url }}" alt="Post media" class="h-32 rounded-lg object-cover">
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="approvePost({{ $post->id }})" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 text-sm font-medium transition-colors">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="rejectPost({{ $post->id }})" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 text-sm font-medium transition-colors">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-12 text-center">
                        <i class="fas fa-check-circle text-4xl text-green-300 dark:text-green-600 mb-4 block"></i>
                        <p class="text-gray-500 dark:text-gray-400">All caught up! No pending approvals.</p>
                    </div>
                    @endforelse
                </div>

                <!-- Invoices Tab -->
                @if($settings->show_invoices)
                <div x-show="activeTab === 'invoices'">
                    @forelse($invoices as $invoice)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-4">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $invoice->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($invoice->total, 2) }}</p>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">{{ ucfirst($invoice->status) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-12 text-center">
                        <i class="fas fa-file-invoice text-4xl text-gray-300 dark:text-gray-600 mb-4 block"></i>
                        <p class="text-gray-500 dark:text-gray-400">No invoices yet</p>
                    </div>
                    @endforelse
                    {{ $invoices->links() }}
                </div>
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">&copy; {{ date('Y') }} {{ $settings->brand_name ?? $agency->name }}. Powered by DigitalMarketingSaaS.</p>
            </div>
        </footer>
    </div>

    <script>
        function approvePost(postId) {
            fetch('{{ route("portal.show", ["token" => $token]) }}/posts/' + postId + '/approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function rejectPost(postId) {
            const reason = prompt('Please provide feedback for rejection:');
            if (reason === null) return;

            fetch('{{ route("portal.show", ["token" => $token]) }}/posts/' + postId + '/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
