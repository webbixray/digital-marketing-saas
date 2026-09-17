<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->title }} - {{ $whiteLabel?->brand_name ?? $agency->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $whiteLabel?->brand_color ?? "#6366f1" }}',
                    }
                }
            }
        }
    </script>
    @if($whiteLabel?->custom_css)
        <style>{{ $whiteLabel->custom_css }}</style>
    @endif
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($whiteLabel?->logo_url)
                            <img src="{{ $whiteLabel->logo_url }}" alt="{{ $whiteLabel->brand_name }}" class="h-8 w-8 object-contain">
                        @endif
                        <span class="text-lg font-semibold text-gray-900">
                            {{ $whiteLabel?->brand_name ?? $agency->name }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $report->period }} Report
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Report Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ $report->title }}</h1>
                <p class="mt-2 text-gray-600">
                    {{ $report->start_date->format('M j, Y') }} - {{ $report->end_date->format('M j, Y') }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Prepared for {{ $client->name }}
                </p>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Posts</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $report->report_data['summary']['total_posts'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Engagement</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($report->report_data['summary']['total_engagement']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">Impressions</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($report->report_data['summary']['total_impressions']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-500">Avg. Engagement Rate</p>
                    <p class="mt-2 text-3xl font-bold text-primary">{{ $report->report_data['summary']['avg_engagement_rate'] }}%</p>
                </div>
            </div>

            <!-- Platform Breakdown -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Platform Breakdown</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Platform</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">Posts</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">Engagement</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">Impressions</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">Eng. Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->report_data['platform_breakdown'] as $platform => $stats)
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4 text-sm font-medium text-gray-900 capitalize">{{ $platform }}</td>
                                    <td class="py-3 px-4 text-sm text-right text-gray-700">{{ $stats['posts_count'] }}</td>
                                    <td class="py-3 px-4 text-sm text-right text-gray-700">{{ number_format($stats['total_engagement']) }}</td>
                                    <td class="py-3 px-4 text-sm text-right text-gray-700">{{ number_format($stats['total_impressions']) }}</td>
                                    <td class="py-3 px-4 text-sm text-right text-gray-700">{{ $stats['avg_engagement_rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Posts -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Posts</h2>
                <div class="space-y-4">
                    @foreach($report->report_data['top_posts'] as $post)
                        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-700">{{ $post['content'] }}</p>
                                    <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                        <span class="capitalize">{{ $post['platform'] }}</span>
                                        <span>{{ $post['published_at'] }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ $post['engagement_rate'] }}%</p>
                                    <p class="text-xs text-gray-500">eng. rate</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Campaigns -->
            @if(count($report->report_data['campaigns']) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Active Campaigns</h2>
                    <div class="space-y-3">
                        @foreach($report->report_data['campaigns'] as $campaign)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $campaign['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $campaign['posts_count'] }} posts</p>
                                </div>
                                <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-800">
                                    {{ ucfirst($campaign['status']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Generated on {{ $report->report_data['generated_at'] }}
                    </p>
                    @if(!($whiteLabel?->hide_powered_by ?? false))
                        <p class="text-xs text-gray-400">
                            Powered by {{ config('app.name') }}
                        </p>
                    @endif
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
