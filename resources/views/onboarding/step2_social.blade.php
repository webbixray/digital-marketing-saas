@extends('layouts.unified')
@section('title', 'Connect Social Accounts')

@section('content')
<x-flash-messages />
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-share-alt text-indigo-600 mr-2"></i>Connect Your Social Media Accounts</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 mb-6">Select the social platforms you want to manage. You can always add more later.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                @foreach($platforms as $key => $name)
                    <label class="relative bg-white dark:bg-gray-800 border-2 {{ isset($connectedAccounts[$key]) ? 'border-green-500' : 'border-gray-200 dark:border-gray-700' }} rounded-xl p-4 cursor-pointer hover:border-indigo-500 transition-colors">
                        <input type="checkbox" name="platforms[]" value="{{ $key }}" class="sr-only" {{ isset($connectedAccounts[$key]) ? 'checked disabled' : '' }}>
                        <div class="text-center">
                            @switch($key)
                                @case('facebook')<i class="fab fa-facebook fa-2x text-blue-600 mb-2"></i>@break
                                @case('instagram')<i class="fab fa-instagram fa-2x text-pink-600 mb-2"></i>@break
                                @case('twitter')<i class="fab fa-twitter fa-2x text-blue-400 mb-2"></i>@break
                                @case('linkedin')<i class="fab fa-linkedin fa-2x text-blue-700 mb-2"></i>@break
                                @case('tiktok')<i class="fab fa-tiktok fa-2x text-black dark:text-white mb-2"></i>@break
                                @case('pinterest')<i class="fab fa-pinterest fa-2x text-red-600 mb-2"></i>@break
                            @endswitch
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $name }}</h4>
                            @if(isset($connectedAccounts[$key]))
                                <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400 mt-1">
                                    <i class="fas fa-check-circle mr-1"></i> Connected
                                </span>
                            @endif
                        </div>
                        <div class="absolute top-2 right-2">
                            <div class="w-5 h-5 rounded border-2 {{ isset($connectedAccounts[$key]) ? 'bg-green-500 border-green-500' : 'border-gray-300 dark:border-gray-600' }} flex items-center justify-center">
                                @if(isset($connectedAccounts[$key]))<i class="fas fa-check text-white text-xs"></i>@endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
            <a href="{{ route('onboarding.step1') }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="{{ route('onboarding.step3') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                Next: Invite Team <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>
@endsection
