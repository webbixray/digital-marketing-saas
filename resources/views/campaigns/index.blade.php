@extends('layouts.modern')

@section('title', 'Campaigns')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Campaigns</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your marketing campaigns.</p>
        </div>
        <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Campaign
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" class="form-input" placeholder="Search campaigns..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Posts</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <i class="fas fa-bullhorn text-indigo-600 dark:text-indigo-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $campaign->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $campaign->type ?? 'General' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $campaign->status === 'active' ? 'badge-success' : ($campaign->status === 'completed' ? 'badge-info' : 'badge-warning') }}">
                                    {{ ucfirst($campaign->status ?? 'draft') }}
                                </span>
                            </td>
                            <td>{{ $campaign->posts_count ?? 0 }}</td>
                            <td>{{ $campaign->start_date ? $campaign->start_date->format('M d, Y') : '—' }}</td>
                            <td>{{ $campaign->end_date ? $campaign->end_date->format('M d, Y') : '—' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-secondary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-bullhorn text-4xl mb-4 block"></i>
                                No campaigns found. <a href="{{ route('campaigns.create') }}" class="text-indigo-600 hover:text-indigo-700">Create your first campaign</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
@endsection
