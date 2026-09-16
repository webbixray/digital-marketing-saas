@extends("layouts.unified")
@section('title', 'Webhook Info')
@section('content')
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12 md:col-span-8">
        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-globe mr-2"></i>Webhook Configuration</h3>
                <div class="card-tools">
                    <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label>Webhook URL</label>
                    <div class="input-group">
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $workflow->webhook_url }}" id="webhookUrl" readonly>
                        <div class="input-group-append">
                            <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors" onclick="copyWebhookUrl()"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <small class="text-muted">Use this URL to trigger the workflow from external services.</small>
                </div>

                <div class="mb-4">
                    <label>Webhook Secret</label>
                    <div class="input-group">
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $workflow->webhook_secret }}" id="webhookSecret" readonly>
                        <div class="input-group-append">
                            <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors" onclick="copySecret()"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <small class="text-muted">Include this secret in your webhook payload for authentication.</small>
                </div>

                <form action="{{ route('workflows.webhook.regenerate', $workflow) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors" onclick="return confirm('Regenerate secret? The old URL will stop working.')">
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

    <div class="col-span-12 md:col-span-4">
        <div class="card card-outline card-info">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-info-circle mr-2"></i>Webhook Logs</h3>
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
    async function copyWebhookUrl() {
        const url = document.getElementById('webhookUrl');
        await dmsaas.copyToClipboard(url.value);
        dmsaas.toast('Webhook URL copied!');
    }
    async function copySecret() {
        const secret = document.getElementById('webhookSecret');
        await dmsaas.copyToClipboard(secret.value);
        dmsaas.toast('Secret copied!');
    }
</script>
@endpush
