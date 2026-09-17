@extends("layouts.unified")
@section('title', 'Step 1: Agency Profile')

@section('content')
<x-flash-messages />
<div class="max-w-3xl mx-auto">
    <!-- Progress -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 mb-6">
        <div class="flex justify-between items-center mb-3">
            <h5 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-rocket text-indigo-600 mr-2"></i>Let's Get You Set Up!</h5>
            <span class="text-sm text-gray-500 dark:text-gray-400">Step 1 of 5</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
            <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: 20%"></div>
        </div>
        <div class="flex justify-between mt-4 text-xs">
            <div class="text-center w-1/5">
                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center mx-auto mb-1 font-bold">1</div>
                <p class="text-indigo-600 font-semibold">Agency</p>
            </div>
            <div class="text-center w-1/5">
                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center mx-auto mb-1 font-bold">2</div>
                <p class="text-gray-500">Social</p>
            </div>
            <div class="text-center w-1/5">
                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center mx-auto mb-1 font-bold">3</div>
                <p class="text-gray-500">Team</p>
            </div>
            <div class="text-center w-1/5">
                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center mx-auto mb-1 font-bold">4</div>
                <p class="text-gray-500">Campaign</p>
            </div>
            <div class="text-center w-1/5">
                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center mx-auto mb-1 font-bold">5</div>
                <p class="text-gray-500">AI</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-building text-indigo-600 mr-2"></i>Tell Us About Your Agency</h3>
        </div>
        <div class="p-6">
            <div class="text-center mb-6">
                <i class="fas fa-building fa-3x text-indigo-600 mb-2"></i>
                <p class="text-gray-500 dark:text-gray-400">This helps us personalize your experience and content suggestions.</p>
            </div>

            <form action="{{ route('onboarding.step1') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agency Name <span class="text-red-500">*</span></label>
                        <input type="text" name="agency_name" value="{{ old('agency_name', $agency->name ?? '') }}" required placeholder="e.g., Acme Marketing Solutions"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('agency_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="url" name="website" value="{{ old('website', $agency->website ?? '') }}" placeholder="https://youragency.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('website')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone <span class="text-red-500">*</span></label>
                        <select name="timezone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select your timezone</option>
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ old('timezone', $agency->timezone ?? config('app.timezone')) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $agency->phone ?? '') }}" placeholder="+1 (555) 000-0000"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
                <div class="mt-6 flex justify-between items-center">
                    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Skip for now</a>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                        Next: Connect Social <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Tip -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mt-4 flex items-center gap-3">
        <i class="fas fa-lightbulb fa-2x text-amber-500"></i>
        <div>
            <h6 class="font-semibold text-amber-800 dark:text-amber-200 mb-1">Quick Tip</h6>
            <p class="text-sm text-amber-700 dark:text-amber-300 mb-0">You can always change these settings later in your agency settings page.</p>
        </div>
    </div>
</div>
@endsection
