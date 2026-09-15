@extends('layouts.unified')
@section('title', 'Changelog')

@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Changelog</h3>
                <div class="card-tools">
                    <span class="badge badge-info">v{{ $currentVersion['full'] ?? '1.0.0' }}</span>
                </div>
            </div>
            <div class="card-body">
                @forelse($changelog ?? [] as $entry)
                <div class="changelog-entry mb-4">
                    <h4 class="mb-2">
                        <span class="badge badge-primary">v{{ $entry['version'] }}</span>
                        <small class="text-muted ml-2">{{ $entry['date'] }}</small>
                    </h4>
                    
                    @if(!empty($entry['added']))
                    <div class="mb-2">
                        <strong class="text-success"><i class="fas fa-plus-circle mr-1"></i> Added</strong>
                        <ul class="ml-4">
                            @foreach($entry['added'] as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(!empty($entry['changed']))
                    <div class="mb-2">
                        <strong class="text-info"><i class="fas fa-sync mr-1"></i> Changed</strong>
                        <ul class="ml-4">
                            @foreach($entry['changed'] as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(!empty($entry['fixed']))
                    <div class="mb-2">
                        <strong class="text-warning"><i class="fas fa-bug mr-1"></i> Fixed</strong>
                        <ul class="ml-4">
                            @foreach($entry['fixed'] as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(!empty($entry['security']))
                    <div class="mb-2">
                        <strong class="text-danger"><i class="fas fa-shield-alt mr-1"></i> Security</strong>
                        <ul class="ml-4">
                            @foreach($entry['security'] as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(!empty($entry['deprecated']))
                    <div class="mb-2">
                        <strong class="text-muted"><i class="fas fa-ban mr-1"></i> Deprecated</strong>
                        <ul class="ml-4">
                            @foreach($entry['deprecated'] as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if(!empty($entry['removed']))
                    <div class="mb-2">
                        <strong class="text-danger"><i class="fas fa-trash mr-1"></i> Removed</strong>
                        <ul class="ml-4">
                            @foreach($entry['removed'] as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @if(!$loop->last)<hr>@endif
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No changelog entries yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Version Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Current Version:</strong> v{{ $currentVersion['full'] ?? '1.0.0' }}</p>
                <p><strong>Codename:</strong> {{ $currentVersion['codename'] ?? 'Genesis' }}</p>
                <p><strong>Release Date:</strong> {{ $currentVersion['release_date'] ?? '2026-09-05' }}</p>
                <p><strong>PHP:</strong> {{ $currentVersion['minimum_php'] ?? '8.4' }}+</p>
                <p><strong>Laravel:</strong> {{ $currentVersion['minimum_laravel'] ?? '13.0' }}+</p>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-code mr-2"></i>API Versions</h3>
            </div>
            <div class="card-body">
                <p><strong>Latest:</strong> <span class="badge badge-success">{{ config('version.api.latest', 'v1') }}</span></p>
                <p><strong>Supported:</strong> {{ implode(', ', config('version.api.supported', ['v1'])) }}</p>
                @if(!empty(config('version.api.deprecated', [])))
                <p><strong>Deprecated:</strong> <span class="badge badge-warning">{{ implode(', ', config('version.api.deprecated')) }}</span></p>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

