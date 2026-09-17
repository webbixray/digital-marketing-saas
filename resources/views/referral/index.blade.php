@extends("layouts.unified")
@section('title', 'Referral Program')

@section('content')

    
        <x-flash-messages />
        <div class="grid grid-cols-12 gap-4 mb-2">
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
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Total Referrals</p>
                            <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['total_referrals'] }}</h3>
                        </div>
                        <i class="fas fa-users text-3xl text-blue-400 dark:text-blue-500"></i>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 dark:text-green-400 font-medium">Credits Earned</p>
                            <h3 class="text-2xl font-bold text-green-900 dark:text-green-100">${{ number_format($stats['total_credits'], 2) }}</h3>
                        </div>
                        <i class="fas fa-dollar-sign text-3xl text-green-400 dark:text-green-500"></i>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">Paying Referrals</p>
                            <h3 class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">{{ $stats['referrals']->where('paid', true)->count() }}</h3>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-yellow-400 dark:text-yellow-500"></i>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-6 border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">Per Referral</p>
                            <h3 class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">$10</h3>
                        </div>
                        <i class="fas fa-gift text-3xl text-indigo-400 dark:text-indigo-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral Link -->
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-link mr-2"></i>Your Referral Link</h3>
                    </div>
                    <div class="p-6">
                        <p>Share your referral link with friends and colleagues. When they sign up and make their first payment, you both get <strong>$10 in credits</strong>!</p>
                        <div class="flex gap-2 mt-4">
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="referralLink" value="{{ $stats['referral_link'] }}" readonly>
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="copyReferralLink()">
                                <i class="fas fa-copy mr-1"></i>Copy
                            </button>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Your referral code: <strong>{{ $stats['referral_code'] }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referrals List -->
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-list mr-2"></i>Your Referrals</h3>
                    </div>
                    <div class="p-6">
                        @if($stats['referrals']->isEmpty())
                            <p class="text-center text-gray-500 dark:text-gray-400 py-4">No referrals yet. Share your link to start earning!</p>
                        @else
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
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
                            </table></div>
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