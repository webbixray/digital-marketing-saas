@extends("layouts.unified")
@section('title', 'Referral Program')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Referral Program</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Referrals</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Referral Stats -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total_referrals'] }}</h3>
                        <p>Total Referrals</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${{ number_format($stats['total_credits'], 2) }}</h3>
                        <p>Credits Earned</p>
                    </div>
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['referrals']->where('paid', true)->count() }}</h3>
                        <p>Paying Referrals</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>$10</h3>
                        <p>Per Referral</p>
                    </div>
                    <div class="icon"><i class="fas fa-gift"></i></div>
                </div>
            </div>
        </div>

        <!-- Referral Link -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-link mr-2"></i>Your Referral Link</h3>
                    </div>
                    <div class="card-body">
                        <p>Share your referral link with friends and colleagues. When they sign up and make their first payment, you both get <strong>$10 in credits</strong>!</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="referralLink" value="{{ $stats['referral_link'] }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-primary" onclick="copyReferralLink()">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                        </div>
                        <p class="text-muted small">Your referral code: <strong>{{ $stats['referral_code'] }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referrals List -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Your Referrals</h3>
                    </div>
                    <div class="card-body">
                        @if($stats['referrals']->isEmpty())
                            <p class="text-center text-muted py-4">No referrals yet. Share your link to start earning!</p>
                        @else
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stats['referrals'] as $referral)
                                        <tr>
                                            <td>{{ $referral['name'] }}</td>
                                            <td>{{ $referral['email'] }}</td>
                                            <td>{{ $referral['joined_at'] }}</td>
                                            <td>
                                                @if($referral['paid'])
                                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Paid</span>
                                                @else
                                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    async function copyReferralLink() {
        const link = document.getElementById('referralLink');
        await dmsaas.copyToClipboard(link.value);
        dmsaas.toast('Referral link copied!');
    }
</script>
@endpush
