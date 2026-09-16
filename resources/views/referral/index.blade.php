@extends("layouts.unified")
@section('title', 'Referral Program')

@section('content')

    
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Referral Program</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="flex gap-2 text-sm text-gray-500">
                    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="text-gray-900 font-medium">Referrals</li>
                </ol>
            </div>
        </div>
    </div>
</div>


    
        <!-- Referral Stats -->
        <div class="grid grid-cols-12 gap-4>
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
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-link mr-2"></i>Your Referral Link</h3>
                    </div>
                    <div class="p-6">
                        <p>Share your referral link with friends and colleagues. When they sign up and make their first payment, you both get <strong>$10 in credits</strong>!</p>
                        <div class="input-group mb-3">
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="referralLink" value="{{ $stats['referral_link'] }}" readonly>
                            <div class="input-group-append">
                                <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="copyReferralLink()">
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
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-list mr-2"></i>Your Referrals</h3>
                    </div>
                    <div class="p-6">
                        @if($stats['referrals']->isEmpty())
                            <p class="text-center text-muted py-4">No referrals yet. Share your link to start earning!</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
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
                                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300"><i class="fas fa-check mr-1"></i>Paid</span>
                                                @else
                                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300"><i class="fas fa-clock mr-1"></i>Pending</span>
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
