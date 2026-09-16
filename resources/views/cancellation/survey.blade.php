@extends("layouts.unified")
@section('title', 'Cancel Subscription')

@section('content')

    
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Cancel Subscription</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="flex gap-2 text-sm text-gray-500">
                    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="hover:text-gray-700"><a href="{{ route('agency.billing') }}">Billing</a></li>
                    <li class="text-gray-900 font-medium">Cancel</li>
                </ol>
            </div>
        </div>
    </div>
</div>


    
        <div class="grid grid-cols-12 gap-4 justify-center>
            <div class="col-span-12 lg:col-span-8">
                <!-- Retention Offer -->
                @if($offer['urgency'] !== 'low')
                    <div class="bg-yellow-50 text-yellow-800 border border-yellow-200 rounded-lg p-4 mb-4">
                        <h5><i class="fas fa-gift mr-2"></i>Wait! Before you go...</h5>
                        <p>{{ $offer['message'] }}</p>
                        <a href="{{ route('agency.billing') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Claim Your Discount</a>
                    </div>
                @endif

                <!-- Cancellation Survey -->
                <div class="card card-warning">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">We're sorry to see you go</h3>
                    </div>
                    <div class="p-6">
                        <p>Help us improve by telling us why you're canceling:</p>

                        <form action="{{ route('cancellation.submit') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason1" name="reason" value="too_expensive" class="custom-control-input" required>
                                    <label class="custom-control-label" for="reason1">Too expensive</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason2" name="reason" value="missing_features" class="custom-control-input">
                                    <label class="custom-control-label" for="reason2">Missing features I need</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason3" name="reason" value="not_using" class="custom-control-input">
                                    <label class="custom-control-label" for="reason3">Not using it enough</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason4" name="reason" value="switching" class="custom-control-input">
                                    <label class="custom-control-label" for="reason4">Switching to another tool</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason5" name="reason" value="other" class="custom-control-input">
                                    <label class="custom-control-label" for="reason5">Other</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="feedback">Additional feedback (optional)</label>
                                <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="feedback" name="feedback" rows="3" placeholder="Tell us what we could do better..."></textarea>
                            </div>

                            <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 inline-flex items-center gap-2 font-medium transition-colors">
                                <i class="fas fa-frown mr-2"></i>Continue with Cancellation
                            </button>
                            <a href="{{ route('agency.billing') }}" class="text-indigo-600 hover:text-indigo-700 underline font-medium">Go Back</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
