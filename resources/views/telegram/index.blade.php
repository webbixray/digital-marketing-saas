@extends('layouts.unified')
@section('title', 'Telegram Integration')

@section('content')
<div class="space-y-6">
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-telegram mr-2"></i>Telegram Bot Integration</h3>
            </div>
            <div class="card-body">
                @if($linked)
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>Your Telegram account is linked!
                </div>
                <p>You can now use the AI Assistant directly from Telegram.</p>
                <p><strong>Bot:</strong> <code>{{ config('telegram.bot_username', '@YourBot') }}</code></p>
                <a href="https://t.me/{{ config('telegram.bot_username', 'YourBot') }}" target="_blank" class="btn btn-primary">
                    <i class="fab fa-telegram mr-1"></i> Open Telegram Bot
                </a>
                <form action="{{ route('telegram.link.unlink') }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Unlink Account</button>
                </form>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>Link your Telegram account to manage your agency from anywhere!
                </div>
                
                <h5>How to link:</h5>
                <ol>
                    <li>Open Telegram and search for <code>{{ config('telegram.bot_username', '@YourBot') }}</code></li>
                    <li>Send <code>/start</code> to the bot</li>
                    <li>Send <code>/link {{ $user->telegram_link_code }}</code></li>
                    <li>Come back here and refresh the page</li>
                </ol>

                <div class="form-group">
                    <label>Your Link Code:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $user->telegram_link_code }}" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" onclick="copyCode()">Copy</button>
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
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-magic mr-2"></i>AI Assistant Commands</h3>
            </div>
            <div class="card-body">
                <p>Once linked, you can use these commands in Telegram:</p>
                <table class="table table-sm">
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
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Security</h3>
            </div>
            <div class="card-body">
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
