@extends('layouts.unified')
@section('title', 'Telegram Integration')

@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4>
    <div class="col-span-12 md:col-span-8">
        <div class="card card-primary card-outline">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fab fa-telegram mr-2"></i>Telegram Bot Integration</h3>
            </div>
            <div class="p-6">
                @if($linked)
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg p-4 mb-4">
                    <i class="fas fa-check-circle mr-2"></i>Your Telegram account is linked!
                </div>
                <p>You can now use the AI Assistant directly from Telegram.</p>
                <p><strong>Bot:</strong> <code>{{ config('telegram.bot_username', '@YourBot') }}</code></p>
                <a href="https://t.me/{{ config('telegram.bot_username', 'YourBot') }}" target="_blank" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                    <i class="fab fa-telegram mr-1"></i> Open Telegram Bot
                </a>
                <form action="{{ route('telegram.link.unlink') }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Unlink Account</button>
                </form>
                @else
                <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Link your Telegram account to manage your agency from anywhere!
                </div>
                
                <h5>How to link:</h5>
                <ol>
                    <li>Open Telegram and search for <code>{{ config('telegram.bot_username', '@YourBot') }}</code></li>
                    <li>Send <code>/start</code> to the bot</li>
                    <li>Send <code>/link {{ $user->telegram_link_code }}</code></li>
                    <li>Come back here and refresh the page</li>
                </ol>

                <div class="mb-4">
                    <label>Your Link Code:</label>
                    <div class="input-group">
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ $user->telegram_link_code }}" readonly>
                        <div class="input-group-append">
                            <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors" onclick="copyCode()">Copy</button>
                        </div>
                    </div>
                </div>

                <form action="{{ route('telegram.link.regenerate') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Generate New Code</button>
                </form>
                @endif
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-magic mr-2"></i>AI Assistant Commands</h3>
            </div>
            <div class="p-6">
                <p>Once linked, you can use these commands in Telegram:</p>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead><tr><th>Command</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>/start</code></td><td>Get started with the bot</td></tr>
                        <tr><td><code>/stats</code></td><td>View dashboard summary</td></tr>
                        <tr><td><code>/posts</code></td><td>List recent posts</td></tr>
                        <tr><td><code>/campaigns</code></td><td>List campaigns</td></tr>
                        <tr><td><code>/clients</code></td><td>List clients</td></tr>
                        <tr><td><code>/invoices</code></td><td>List invoices</td></tr>
                        <tr><td><code>/quota</code></td><td>Check plan usage</td></tr>
                        <tr><td><code>/ai [prompt]</code></td><td>Generate AI content</td></tr>
                        <tr><td><code>/help</code></td><td>Show all commands</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="col-span-12 md:col-span-4">
        <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-shield-alt mr-2"></i>Security</h3>
            </div>
            <div class="p-6">
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success mr-2"></i> Unique link code per user</li>
                    <li><i class="fas fa-check text-success mr-2"></i> Webhook signature verification</li>
                    <li><i class="fas fa-check text-success mr-2"></i> No credentials stored in Telegram</li>
                    <li><i class="fas fa-check text-success mr-2"></i> Unlink anytime</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('scripts')
<script>
    async function copyCode() {
        const code = document.querySelector('input[readonly]').value;
        await dmsaas.copyToClipboard(code);
        dmsaas.toast('Code copied!');
    }
</script>
@endpush
