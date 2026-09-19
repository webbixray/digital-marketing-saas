@extends("layouts.unified")
@section('title', 'Create A/B Test')

@section('content')

<x-flash-messages />

<!-- Breadcrumb -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white m-0">Create A/B Test</h1>
    </div>
    <div class="flex items-center justify-start sm:justify-end">
        <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400">
            <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('ab-testing.index') }}" class="hover:text-indigo-600">A/B Testing</a></li>
            <li>/</li>
            <li class="text-gray-900 dark:text-white font-medium">Create</li>
        </ol>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-10 gap-6 justify-center">
    <div class="lg:col-span-10">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Design Your Test</h3>
            </div>
            <form action="{{ route('ab-testing.store') }}" method="POST">
                @csrf
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Name <span class="text-red-600 dark:text-red-400">*</span></label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Caption Length Test">
                                @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="md:col-span-1">
                            <div class="mb-4">
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Type <span class="text-red-600 dark:text-red-400">*</span></label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="type" name="type" required>
                                    <option value="content">Content</option>
                                    <option value="timing">Timing</option>
                                    <option value="hashtag">Hashtag</option>
                                    <option value="media">Media</option>
                                </select>
                                @error('type')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="social_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Social Account <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="social_account_id" name="social_account_id" required>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ ucfirst($account->platform) }} - {{ $account->platform_username }}</option>
                                @endforeach
                            </select>
                            @error('social_account_id')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform <span class="text-red-600 dark:text-red-400">*</span></label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="platform" name="platform" required>
                                <option value="facebook">Facebook</option>
                                <option value="instagram">Instagram</option>
                                <option value="twitter">Twitter</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="tiktok">TikTok</option>
                                <option value="pinterest">Pinterest</option>
                            </select>
                            @error('platform')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="hypothesis" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hypothesis (optional)</label>
                        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="hypothesis" name="hypothesis" rows="2" placeholder="e.g., Shorter captions will get more engagement">{{ old('hypothesis') }}</textarea>
                        @error('hypothesis')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="sample_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sample Size (per variant) <span class="text-red-600 dark:text-red-400">*</span></label>
                        <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="sample_size" name="sample_size" value="{{ old('sample_size', 100) }}" min="50" max="10000" required>
                        <small class="text-sm text-gray-500 dark:text-gray-400">Minimum 50 per variant. Recommended: 100-500 for statistical significance.</small>
                        @error('sample_size')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Variant A (Control)</h3>
                            </div>
                            <div class="p-6">
                                <div class="mb-4">
                                    <label for="variant_a_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="variant_a_content" name="variant_a_content" rows="4" required placeholder="Enter your control variant content...">{{ old('variant_a_content') }}</textarea>
                                    @error('variant_a_content')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Variant B (Treatment)</h3>
                            </div>
                            <div class="p-6">
                                <div class="mb-4">
                                    <label for="variant_b_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="variant_b_content" name="variant_b_content" rows="4" required placeholder="Enter your treatment variant content...">{{ old('variant_b_content') }}</textarea>
                                    @error('variant_b_content')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                        <i class="fas fa-flask mr-2"></i>Create Test
                    </button>
                    <a href="{{ route('ab-testing.index') }}" class="text-indigo-600 hover:text-indigo-700 underline font-medium">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
