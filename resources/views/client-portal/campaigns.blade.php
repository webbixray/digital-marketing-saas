@extends('layouts.unified')

@section('title', 'Campaigns')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Campaigns</h1>
            <p class="text-gray-600 mt-1">View and manage all your campaigns</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                <a href="{{ route('client-portal.v2.campaigns') }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ !request('status') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    All <span class="ml-1 text-xs bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full">{{ array_sum($statusCounts) }}</span>
                </a>
                <a href="{{ route('client-portal.v2.campaigns', ['status' => 'active']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'active' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Active <span class="ml-1 text-xs bg-green-100 text-green-600 py-0.5 px-2 rounded-full">{{ $statusCounts['active'] ?? 0 }}</span>
                </a>
                <a href="{{ route('client-portal.v2.campaigns', ['status' => 'paused']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'paused' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Paused <span class="ml-1 text-xs bg-yellow-100 text-yellow-600 py-0.5 px-2 rounded-full">{{ $statusCounts['paused'] ?? 0 }}</span>
                </a>
                <a href="{{ route('client-portal.v2.campaigns', ['status' => 'completed']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'completed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Completed <span class="ml-1 text-xs bg-blue-100 text-blue-600 py-0.5 px-2 rounded-full">{{ $statusCounts['completed'] ?? 0 }}</span>
                </a>
                <a href="{{ route('client-portal.v2.campaigns', ['status' => 'draft']) }}" 
                   class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 {{ request('status') === 'draft' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Draft <span class="ml-1 text-xs bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full">{{ $statusCounts['draft'] ?? 0 }}</span>
                </a>
            </nav>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 bg-gray-50">
            <form method="GET" action="{{ route('client-portal.v2.campaigns') }}" class="flex flex-col sm:flex-row gap-4">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search campaigns..." 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="w-full sm:w-48">
                    <select name="client_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $campaign->name }}</span>
                                    @if($campaign->description)
                                        <p class="text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($campaign->description, 60) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $campaign->client->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ \App\Models\Campaign::CAMPAIGN_TYPES[$campaign->type] ?? ucfirst($campaign->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $campaign->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $campaign->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    <span class="w-2 h-2 rounded-full mr-1.5
                                        {{ $campaign->status === 'active' ? 'bg-green-500' : '' }}
                                        {{ $campaign->status === 'paused' ? 'bg-yellow-500' : '' }}
                                        {{ $campaign->status === 'completed' ? 'bg-blue-500' : '' }}
                                        {{ $campaign->status === 'draft' ? 'bg-gray-500' : '' }}">
                                    </span>
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $campaign->posts_count }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $campaign->start_date ? $campaign->start_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('client-portal.v2.analytics', ['campaign_id' => $campaign->id]) }}" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    Analytics
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-bullhorn text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-500 text-lg">No campaigns found</p>
                                    <p class="text-gray-400 text-sm mt-1">Create your first campaign to get started</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($campaigns->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
