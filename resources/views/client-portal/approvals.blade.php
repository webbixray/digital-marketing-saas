@extends('layouts.unified')

@section('title', 'Content Approvals')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Content Approval Queue</h1>
        <p class="text-gray-600 mt-1">Review and approve content before it goes live</p>
    </div>

    <div class="space-y-4">
        @forelse ($approvals as $approval)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $approval->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $approval->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $approval->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($approval->status) }}
                            </span>
                            @if($approval->socialPost)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                    {{ ucfirst($approval->socialPost->platform ?? 'Unknown') }}
                                </span>
                            @endif
                        </div>

                        @if($approval->socialPost)
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ Str::limit($approval->socialPost->content ?? 'No content preview', 300) }}</p>
                                @if($approval->socialPost->image_url)
                                    <img src="{{ $approval->socialPost->image_url }}" alt="Post preview" class="mt-3 rounded-lg max-h-48 object-cover">
                                @endif
                            </div>
                        @endif

                        <p class="text-xs text-gray-500">Submitted {{ $approval->created_at->diffForHumans() }}</p>

                        @if($approval->status === 'pending')
                            <div class="flex space-x-3 mt-4">
                                <button onclick="approveContent({{ $approval->id }})"
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">
                                    <i class="fas fa-check mr-1"></i> Approve
                                </button>
                                <button onclick="showRejectModal({{ $approval->id }})"
                                        class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                                    <i class="fas fa-times mr-1"></i> Reject
                                </button>
                            </div>
                        @endif

                        @if($approval->rejection_reason)
                            <div class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-sm text-red-700"><strong>Rejection reason:</strong> {{ $approval->rejection_reason }}</p>
                            </div>
                        @endif

                        @if($approval->reviewed_at)
                            <p class="text-xs text-gray-500 mt-2">Reviewed {{ $approval->reviewed_at->diffForHumans() }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center text-gray-500">
                <i class="fas fa-clipboard-check text-4xl mb-3"></i>
                <p class="text-lg font-medium">All caught up!</p>
                <p class="text-sm mt-1">No pending approvals at this time.</p>
            </div>
        @endforelse
    </div>

    @if($approvals->hasPages())
        <div class="mt-6">
            {{ $approvals->links() }}
        </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Content</h3>
        <textarea id="reject-reason" rows="3" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-red-500 focus:border-red-500" placeholder="Please provide a reason for rejection..."></textarea>
        <div class="flex justify-end space-x-3 mt-4">
            <button onclick="closeRejectModal()" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</button>
            <button onclick="submitReject()" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">Submit Rejection</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentApprovalId = null;

function approveContent(id) {
    fetch(`/client-portal-v2/{{ $client->id }}/approvals/${id}/approve`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}

function showRejectModal(id) {
    currentApprovalId = id;
    document.getElementById('reject-modal').classList.remove('hidden');
    document.getElementById('reject-modal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
    document.getElementById('reject-modal').classList.remove('flex');
    currentApprovalId = null;
}

function submitReject() {
    const reason = document.getElementById('reject-reason').value;
    fetch(`/client-portal-v2/{{ $client->id }}/approvals/${currentApprovalId}/reject`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ reason })
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}
</script>
@endpush
@endsection
