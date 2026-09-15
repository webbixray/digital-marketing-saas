<div class="card">
    <div class="card-header">
        <h3 class="card-title">Comments</h3>
    </div>
    <div class="card-body">
        <!-- Comment Form -->
        <form id="commentForm" class="mb-4" onsubmit="submitComment(event)">
            @csrf
            <div class="d-flex gap-2">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=random&size=40" class="rounded-circle" width="40" height="40" alt="">
                <div class="flex-grow-1">
                    <textarea id="commentBody" class="form-control" rows="2" placeholder="Write a comment..." required></textarea>
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Post Comment</button>
                </div>
            </div>
        </form>

        <!-- Comments List -->
        <div id="commentsList">
            @foreach($comments ?? [] as $comment)
            <div class="d-flex gap-2 mb-3">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name ?? 'User') }}&background=random&size=32" class="rounded-circle" width="32" height="32" alt="">
                <div>
                    <strong>{{ $comment->user->name ?? 'Unknown' }}</strong>
                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                    <p class="mb-0">{{ $comment->body }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push("scripts")
<script>
    const commentableType = @json( ?? null);
    const commentableId = @json( ?? null);

    async function submitComment(e) {
        e.preventDefault();
        const body = document.getElementById('commentBody').value;
        if (!body.trim()) return;

        try {
            const response = await dmsaas.request('{{ route('comments.store') }}', {
                method: 'POST',
                body: JSON.stringify({
                    commentable_type: commentableType,
                    commentable_id: commentableId,
                    body: body,
                }),
            });

            if (response.ok) {
                dmsaas.toast('Comment posted!');
                location.reload();
            }
        } catch (err) {
            dmsaas.toast('Failed to post comment.', 'error');
        }
    }
</script>
@endpush
