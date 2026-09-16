@extends('layouts.public-unified')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-xl p-8 text-center">
            <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Two-Factor Authentication</h1>

            @if($user->two_factor_enabled)
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 mb-4">
                    <i class="fas fa-check-circle mr-2"></i>Two-factor authentication is ENABLED
                </div>
                <form action="{{ route('two-factor.disable') }}" method="POST">
                    @csrf
                    <button class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700">
                        <i class="fas fa-times mr-1"></i> Disable 2FA
                    </button>
                </form>
            @else
                <p class="text-gray-600 mb-4">Protect your account with two-factor authentication.</p>
                <button class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700" id="enable2fa">
                    <i class="fas fa-qrcode mr-1"></i> Enable 2FA
                </button>
                <div id="setupForm" class="mt-4 hidden">
                    <div class="mb-3"><img id="qrCode" class="mx-auto" style="max-width: 200px;" alt="Two-factor authentication QR code"></div>
                    <p class="text-gray-500">Scan the QR code with your authenticator app, then enter the code:</p>
                    <form action="{{ route('two-factor.verify') }}" method="POST" class="mt-4">
                        @csrf
                        <div class="mb-3"><input type="text" name="code" class="w-full px-4 py-2 border border-gray-300 rounded-lg" maxlength="6" placeholder="000000" required></div>
                        <button class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700">Verify & Enable</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const enableBtn = document.getElementById('enable2fa');
        if (enableBtn) {
            enableBtn.addEventListener('click', async function() {
                try {
                    const response = await fetch('{{ route("two-factor.enable") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();
                    if (data.qr_code) {
                        document.getElementById('qrCode').src = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' + encodeURIComponent(data.qr_code);
                        document.getElementById('setupForm').classList.remove('hidden');
                        if (window.dmsaas) dmsaas.toast('2FA enabled! Please scan the QR code.');
                    }
                } catch (err) {
                    if (window.dmsaas) dmsaas.toast('Failed to enable 2FA.', 'error');
                }
            });
        }
    });
</script>
@endpush
