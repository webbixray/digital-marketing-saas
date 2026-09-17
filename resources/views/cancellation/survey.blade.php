@extends("layouts.unified")
@section('title', 'Cancel Subscription')

@section('content')

    
        <x-flash-messages />
        <div class="grid grid-cols-12 gap-4 mb-2">
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

    
        <div class="grid grid-cols-12 gap-4 justify-center">
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
                <div class="bg-white rounded-xl shadow-md border border-yellow-200 dark:bg-gray-800 dark:border-yellow-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">We're sorry to see you go</h3>
                    </div>
                    <div class="p-6">
                        <p>Help us improve by telling us why you're canceling:</p>

                        <form action="{{ route('cancellation.submit') }}" method="POST">
                            @csrf
                            <div class="mb-4 space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" id="reason1" name="reason" value="too_expensive" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" required>
                                    <span>Too expensive</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" id="reason2" name="reason" value="missing_features" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Missing features I need</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" id="reason3" name="reason" value="not_using" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Not using it enough</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" id="reason4" name="reason" value="switching" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Switching to another tool</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" id="reason5" name="reason" value="other" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Other</span>
                                </label>
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
</section>