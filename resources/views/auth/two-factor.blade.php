@extends('layouts.unified')
@section('title', 'Two-Factor Authentication')

@section('content')
<div class="space-y-6">
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Two-Factor Authentication</h3></div>
            <div class="card-body text-center">
                @if($user->two_factor_enabled)
                    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Two-factor authentication is ENABLED</div>
                    <form action="{{ route('two-factor.disable') }}" method="POST">@csrf
                        <button class="btn btn-danger"><i class="fas fa-times mr-1"></i> Disable 2FA</button>
                    </form>
                @else
                    <p>Protect your account with two-factor authentication.</p>
                    <button class="btn btn-primary" id="enable2fa"><i class="fas fa-qrcode mr-1"></i> Enable 2FA</button>
                    <div id="setupForm" class="mt-4 d-none">
                        <div class="mb-3"><img id="qrCode" class="img-fluid" style="max-width: 200px;"></div>
                        <p class="text-muted">Scan the QR code with your authenticator app, then enter the code:</p>
                        <form action="{{ route('two-factor.verify') }}" method="POST">@csrf
                            <div class="form-group"><input type="text" name="code" class="form-control" maxlength="6" placeholder="000000" required></div>
                            <button class="btn btn-success">Verify & Enable</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('scripts')
<script>
$('#enable2fa').click(function() {
    $.post('{{ route("two-factor.enable") }}', {_token: '{{ csrf_token() }}'}, function(res) {
        $('#qrCode').attr('src', 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' + encodeURIComponent(res.qr_code));
        $('#setupForm').removeClass('d-none');
    });
});
</script>
@endpush
