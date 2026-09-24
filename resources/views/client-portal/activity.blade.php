@extends('layouts.unified')

@section('title', 'Activity Feed')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Activity Feed</h1>
            <p class="text-gray-600 mt-1">Recent activity for {{ $client->name ?? 'your account' }}</p>
        </div>
        <div class="flex space-x-3">
            <select id="filter-type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Types</option>
                <option value="approval">Approvals</option>
                <option value="invoice">Invoices</option>
                <option value="report">Reports</option>
                <option value="message">Messages</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @forelse ($activities as $activity)
            <div class="px-6 py-4 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition">
                <div class="flex items-start space-x-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                        {{ $activity->type === 'approval' ? 'bg-green-100 text-green-600' : '' }}
                        {{ $activity->type === 'invoice' ? 'bg-blue-100 text-blue-600' : '' }}
                        {{ $activity->type === 'report' ? 'bg-purple-100 text-purple-600' : '' }}
                        {{ $activity->type === 'message' ? 'bg-gray-100 text-gray-600' : '' }}
                        {{ !in_array($activity->type, ['approval', 'invoice', 'report', 'message']) ? 'bg-gray-100 text-gray-600' : '' }}">
                        @if($activity->type === 'approval')
                            <i class="fas fa-check"></i>
                        @elseif($activity->type === 'invoice')
                            <i class="fas fa-file-invoice-dollar"></i>
                        @elseif($activity->type === 'report')
                            <i class="fas fa-chart-bar"></i>
                        @else
                            <i class="fas fa-bell"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $activity->title }}</h3>
                            <span class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                        @if($activity->body)
                            <p class="text-sm text-gray-600 mt-1">{{ $activity->body }}</p>
                        @endif
                        @if($activity->action_url)
                            <a href="{{ $activity->action_url }}" class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">View details &rarr;</a>
                        @endif
                    </div>
                    @if(!$activity->is_read)
                        <button onclick="markRead({{ $activity->id }})"
                                class="text-xs text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                            Mark as read
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No activity yet</p>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
function markRead(id) {
    fetch(`/client-portal-v2/{{ $client->id }}/notifications/${id}/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}
</script>
@endpush
@endsection
