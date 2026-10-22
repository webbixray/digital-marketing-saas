<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Analytics Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        .header p { color: #64748b; margin-top: 0.25rem; }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card .label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .value { font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem; }
        .stat-card .change { font-size: 0.75rem; color: #16a34a; margin-top: 0.25rem; }

        .card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }

        .chart-container { height: 250px; display: flex; align-items: flex-end; gap: 2px; padding-top: 1rem; border-bottom: 1px solid #e2e8f0; }
        .chart-bar { flex: 1; background: #4f46e5; border-radius: 3px 3px 0 0; min-height: 2px; position: relative; opacity: 0.8; transition: opacity 0.2s; }
        .chart-bar:hover { opacity: 1; }
        .chart-bar .tooltip { display: none; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #1e293b; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem; white-space: nowrap; }
        .chart-bar:hover .tooltip { display: block; }

        .breakdown-list { list-style: none; }
        .breakdown-item { display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
        .breakdown-item:last-child { border-bottom: none; }
        .breakdown-label { flex: 1; display: flex; align-items: center; gap: 0.5rem; }
        .breakdown-icon { width: 12px; height: 12px; border-radius: 3px; }
        .breakdown-value { font-weight: 600; color: #475569; }
        .breakdown-bar { width: 100px; height: 6px; background: #f1f5f9; border-radius: 3px; margin: 0 1rem; overflow: hidden; }
        .breakdown-bar-fill { height: 100%; background: #4f46e5; border-radius: 3px; }

        .asset-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
        .asset-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px; }
        .asset-item img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; background: #e2e8f0; }
        .asset-item .asset-name { font-size: 0.875rem; font-weight: 500; flex: 1; }
        .asset-item .asset-count { font-size: 0.75rem; color: #64748b; }

        .timeline { display: flex; flex-direction: column; gap: 0.5rem; }
        .timeline-item { display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0; }
        .timeline-date { width: 80px; font-size: 0.75rem; color: #64748b; }
        .timeline-bar { height: 20px; background: #e2e8f0; border-radius: 4px; flex: 1; overflow: hidden; }
        .timeline-bar-fill { height: 100%; background: linear-gradient(90deg, #4f46e5, #7c3aed); border-radius: 4px; }
        .timeline-count { width: 30px; text-align: right; font-weight: 600; font-size: 0.875rem; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Media Analytics</h1>
            <p>Overview of your media library usage and storage</p>
        </div>

        <!-- Summary Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="label">Total Assets</div>
                <div class="value">{{ number_format($summary['total_assets'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Storage Used</div>
                <div class="value">{{ $summary['total_storage_human'] ?? '0 B' }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Usage</div>
                <div class="value">{{ number_format($summary['total_usage_count'] ?? 0) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Uploads Today</div>
                <div class="value">{{ $summary['uploads_today'] ?? 0 }}</div>
            </div>
        </div>

        <!-- Storage Usage Chart -->
        <div class="card">
            <h2>Storage Usage Trend (30 days)</h2>
            <div class="chart-container" id="storageChart">
                @php
                    $maxSize = max(array_column($storageTrends, 'size_bytes')) ?: 1;
                @endphp
                @foreach($storageTrends as $trend)
                    @php
                        $height = $trend['size_bytes'] > 0 ? max(2, ($trend['size_bytes'] / $maxSize) * 200) : 2;
                    @endphp
                    <div class="chart-bar" style="height: {{ $height }}px;">
                        <span class="tooltip">{{ $trend['date'] }}: {{ $trend['size_bytes'] ? round($trend['size_bytes'] / 1048576, 1) . ' MB' : '0 B' }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid-2">
            <!-- File Type Breakdown -->
            <div class="card">
                <h2>File Type Breakdown</h2>
                <ul class="breakdown-list">
                    @foreach($fileTypeBreakdown as $type)
                        <li class="breakdown-item">
                            <span class="breakdown-label">
                                <span class="breakdown-icon" style="background: {{ match($type['file_type']) { 'image' => '#4f46e5', 'video' => '#dc2626', 'document' => '#16a34a', default => '#94a3b8' } }};"></span>
                                {{ ucfirst($type['file_type']) }}
                            </span>
                            <div class="breakdown-bar">
                                <div class="breakdown-bar-fill" style="width: {{ $type['percentage'] }}%;"></div>
                            </div>
                            <span class="breakdown-value">{{ $type['human_size'] }} ({{ $type['count'] }})</span>
                        </li>
                    @endforeach
                    @if(empty($fileTypeBreakdown))
                        <li class="breakdown-item"><span class="breakdown-label">No files uploaded yet</span></li>
                    @endif
                </ul>
            </div>

            <!-- Upload Activity -->
            <div class="card">
                <h2>Upload Activity (30 days)</h2>
                <div class="timeline">
                    @php
                        $maxUploads = max(array_column($uploadActivity, 'uploads')) ?: 1;
                    @endphp
                    @foreach(array_slice($uploadActivity, -14) as $day)
                        <div class="timeline-item">
                            <span class="timeline-date">{{ \Carbon\Carbon::parse($day['date'])->format('M d') }}</span>
                            <div class="timeline-bar">
                                <div class="timeline-bar-fill" style="width: {{ $day['uploads'] > 0 ? ($day['uploads'] / $maxUploads) * 100 : 0 }}%;"></div>
                            </div>
                            <span class="timeline-count">{{ $day['uploads'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Most Used Assets -->
        <div class="card">
            <h2>Most Used Assets</h2>
            <div class="asset-list">
                @forelse($mostUsedAssets as $asset)
                    <div class="asset-item">
                        <img src="{{ $asset->thumbnail_url }}" alt="{{ $asset->name }}">
                        <div class="asset-name">{{ Str::limit($asset->name, 25) }}</div>
                        <div class="asset-count">{{ $asset->usage_count }} uses</div>
                    </div>
                @empty
                    <div class="asset-list" style="padding: 1rem; color: #64748b;">No asset usage data yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</body>
</html>
