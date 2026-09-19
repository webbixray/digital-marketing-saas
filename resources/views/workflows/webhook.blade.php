@extends("layouts.unified")
@section('title', 'Webhook Info')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Webhook Configuration</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage webhook settings for your workflow.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-globe mr-2"></i>Webhook Configuration</h3>
                    <a href="{{ route('workflows.show', $workflow) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors text-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</label>
                        <div class="flex gap-2">
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $workflow->webhook_url }}" id="webhookUrl" readonly>
                            <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors" onclick="copyWebhookUrl()"><i class="fas fa-copy"></i></button>
                        </div>
                        <small class="text-gray-500 dark:text-gray-400">Use this URL to trigger the workflow from external services.</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook Secret</label>
                        <div class="flex gap-2">
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $workflow->webhook_secret }}" id="webhookSecret" readonly>
                            <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors" onclick="copySecret()"><i class="fas fa-copy"></i></button>
                        </div>
                        <small class="text-gray-500 dark:text-gray-400">Include this secret in your webhook payload for authentication.</small>
                    </div>

                    <form action="{{ route('workflows.webhook.regenerate', $workflow) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors" onclick="return confirm('Regenerate secret? The old URL will stop working.')">
                            <i class="fas fa-sync mr-1"></i> Regenerate Secret
                        </button>
                    </form>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <h5 class="font-medium text-gray-900 dark:text-white">Example Payload</h5>
                    <pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded"><code>{
  "event": "custom_event",
  "data": {
    "key": "value"
  }
}</code></pre>

                    <h5 class="font-medium text-gray-900 dark:text-white">Example cURL</h5>
                    <pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded"><code>curl -X POST {{ $workflow->webhook_url }} \
  -H "Content-Type: application/json" \
  -d '{"event": "custom_event", "data": {"key": "value"}}'</code></pre>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-info-circle mr-2"></i>Webhook Logs</h3>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($webhookLogs as $log)
                        <li class="p-3">
                            <div class="flex justify-between items-center">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $log->status === 'processed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">{{ ucfirst($log->status) }}</span>
                                <small>{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <small class="text-gray-500 dark:text-gray-400">{{ $log->event_type }} from {{ $log->ip_address }}</small>
                        </li>
                        @empty
                        <li class="p-3 text-center text-gray-500 dark:text-gray-400">No webhook calls yet</li>
                        @endforelse
                    </ul>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $webhookLogs->links() }}</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
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
 @endsection
