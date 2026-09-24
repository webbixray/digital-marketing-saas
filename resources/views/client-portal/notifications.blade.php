@extends('layouts.unified')

@section('title', 'Notifications')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
            <p class="text-gray-600 mt-1">{{ $unreadCount ?? $notifications->where('is_read', false)->count() }} unread notifications</p>
        </div>
        <div class="flex space-x-3">
            <select id="filter-type" onchange="applyFilter()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Types</option>
                @foreach($typeCounts as $type => $count)
                    <option value="{{ $type }}">{{ ucfirst($type) }} ({{ $count }})</option>
                @endforeach
            </select>
            <button onclick="markAllRead()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Mark All as Read
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @forelse ($notifications as $notification)
            <div class="px-6 py-4 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition {{ !$notification->is_read ? 'bg-indigo-50/30' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4">
                        <div class="w-2 h-2 rounded-full mt-2 {{ $notification->is_read ? 'bg-transparent' : 'bg-indigo-600' }}"></div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $notification->title }}</h3>
                            @if($notification->body)
                                <p class="text-sm text-gray-600 mt-1">{{ $notification->body }}</p>
                            @endif
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ ucfirst($notification->type) }}</span>
                            </div>
                        </div>
                    </div>
                    @if($notification->action_url)
                        <a href="{{ $notification->action_url }}" class="text-sm text-indigo-600 hover:text-indigo-800 whitespace-nowrap">View</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-bell-slash text-4xl mb-3"></i>
                <p>No notifications</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function applyFilter() {
    const type = document.getElementById('filter-type').value;
    const url = new URL(window.location.href);
    if (type) url.searchParams.set('type', type);
    else url.searchParams.delete('type');
    window.location.href = url.toString();
}

function markAllRead() {
    fetch('/client-portal-v2/{{ $client->id }}/notifications/mark-all-read', {
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
