@extends("layouts.unified")
@section('title', 'Webhook Info')
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-globe mr-2"></i>Webhook Configuration</h3>
                <div class="card-tools">
                    <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Webhook URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $workflow->webhook_url }}" id="webhookUrl" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" onclick="copyWebhookUrl()"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <small class="text-muted">Use this URL to trigger the workflow from external services.</small>
                </div>

                <div class="form-group">
                    <label>Webhook Secret</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $workflow->webhook_secret }}" id="webhookSecret" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" onclick="copySecret()"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <small class="text-muted">Include this secret in your webhook payload for authentication.</small>
                </div>

                <form action="{{ route('workflows.webhook.regenerate', $workflow) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Regenerate secret? The old URL will stop working.')">
                        <i class="fas fa-sync mr-1"></i> Regenerate Secret
                    </button>
                </form>

                <hr>

                <h5>Example Payload</h5>
                <pre class="bg-light p-3 rounded"><code>{
  "event": "custom_event",
  "data": {
    "key": "value"
  }
}</code></pre>

                <h5 class="mt-3">Example cURL</h5>
                <pre class="bg-light p-3 rounded"><code>curl -X POST {{ $workflow->webhook_url }} \
  -H "Content-Type: application/json" \
  -d '{"event": "custom_event", "data": {"key": "value"}}'</code></pre>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Webhook Logs</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($webhookLogs as $log)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="badge badge-{{ $log->status === 'processed' ? 'success' : 'warning' }}">{{ ucfirst($log->status) }}</span>
                            <small>{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                        <small class="text-muted">{{ $log->event_type }} from {{ $log->ip_address }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">No webhook calls yet</li>
                    @endforelse
                </ul>
            </div>
            <div class="card-footer">{{ $webhookLogs->links() }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyWebhookUrl() {
    const url = document.getElementById('webhookUrl');
    url.select();
    document.execCommand('copy');
    toastr.success('Webhook URL copied!');
}
function copySecret() {
    const secret = document.getElementById('webhookSecret');
    secret.select();
    document.execCommand('copy');
    toastr.success('Secret copied!');
}
</script>
@endpush
