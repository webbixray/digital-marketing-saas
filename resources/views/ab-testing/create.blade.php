@extends('layouts.unified')
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

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Design Your Test</h3>
            </div>
            <form action="{{ route('ab-testing.store') }}" method="POST" x-data="variantBuilder()">
                @csrf
                <div class="p-6 space-y-6">
                    <!-- Test Configuration -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Name <span class="text-red-600">*</span></label>
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Caption Length Test">
                            @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Type <span class="text-red-600">*</span></label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="type" name="type" required x-model="testType" @change="updateTypeHints()">
                                <option value="content">Content Variants</option>
                                <option value="timing">Posting Times</option>
                                <option value="hashtag">Hashtag Sets</option>
                                <option value="media">Media Types</option>
                            </select>
                            @error('type')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="social_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Social Account <span class="text-red-600">*</span></label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="social_account_id" name="social_account_id" required>
                                <option value="">Select Account</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('social_account_id') == $account->id ? 'selected' : '' }}>{{ ucfirst($account->platform) }} - {{ $account->platform_username }}</option>
                                @endforeach
                            </select>
                            @error('social_account_id')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform <span class="text-red-600">*</span></label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="platform" name="platform" required>
                                <option value="facebook" {{ old('platform') == 'facebook' ? 'selected' : '' }}>Facebook</option>
                                <option value="instagram" {{ old('platform') == 'instagram' ? 'selected' : '' }}>Instagram</option>
                                <option value="twitter" {{ old('platform') == 'twitter' ? 'selected' : '' }}>Twitter</option>
                                <option value="linkedin" {{ old('platform') == 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
                                <option value="tiktok" {{ old('platform') == 'tiktok' ? 'selected' : '' }}>TikTok</option>
                                <option value="pinterest" {{ old('platform') == 'pinterest' ? 'selected' : '' }}>Pinterest</option>
                            </select>
                            @error('platform')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="hypothesis" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hypothesis</label>
                        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="hypothesis" name="hypothesis" rows="2" placeholder="e.g., Shorter captions will get more engagement">{{ old('hypothesis') }}</textarea>
                        @error('hypothesis')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label for="sample_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sample Size (per variant) <span class="text-red-600">*</span></label>
                        <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" id="sample_size" name="sample_size" value="{{ old('sample_size', 100) }}" min="50" max="10000" required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 50 per variant. Recommended: 100-500 for statistical significance.</p>
                        @error('sample_size')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                    </div>

                    <!-- Type-specific hint -->
                    <div x-show="typeHint" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-3">
                        <p class="text-sm text-indigo-800 dark:text-indigo-300"><i class="fas fa-lightbulb mr-2"></i><span x-text="typeHint"></span></p>
                    </div>

                    <!-- Variants -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Variant A -->
                        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold">A</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Control Variant</h3>
                            </div>
                            <div class="p-4">
                                <div class="mb-3">
                                    <label for="variant_a_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content <span class="text-red-600">*</span></label>
                                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" id="variant_a_content" name="variant_a_content" rows="4" required x-model="variantA" @input="updatePreview()" placeholder="Enter your control variant content...">{{ old('variant_a_content') }}</textarea>
                                    @error('variant_a_content')<span class="text-red-500 text-xs mt-1">{{ $message }}</span>@enderror
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="variantA.length"></span> characters
                                </div>
                            </div>
                        </div>

                        <!-- Variant B -->
                        <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 flex items-center justify-center text-sm font-bold">B</span>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Treatment Variant</h3>
                            </div>
                            <div class="p-4">
                                <div class="mb-3">
                                    <label for="variant_b_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content <span class="text-red-600">*</span></label>
                                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" id="variant_b_content" name="variant_b_content" rows="4" required x-model="variantB" @input="updatePreview()" placeholder="Enter your treatment variant content...">{{ old('variant_b_content') }}</textarea>
                                    @error('variant_b_content')<span class="text-red-500 text-xs mt-1">{{ $message }}</span>@enderror
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="variantB.length"></span> characters
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <div x-show="variantA || variantB" x-transition class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><i class="fas fa-eye mr-1"></i>Preview</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="bg-white dark:bg-gray-800 rounded p-3 border border-gray-200 dark:border-gray-700">
                                <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-1">Variant A</p>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="variantA || '(empty)'"></p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-3 border border-gray-200 dark:border-gray-700">
                                <p class="text-xs font-medium text-yellow-600 dark:text-yellow-400 mb-1">Variant B</p>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="variantB || '(empty)'"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                        <i class="fas fa-flask"></i>Create Test
                    </button>
                    <a href="{{ route('ab-testing.index') }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-4 space-y-4">
        <!-- Tips Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><i class="fas fa-info-circle mr-1"></i>Best Practices</h3>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div class="flex gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <p class="text-gray-700 dark:text-gray-300">Test only one variable at a time</p>
                </div>
                <div class="flex gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <p class="text-gray-700 dark:text-gray-300">Run tests for at least 7 days</p>
                </div>
                <div class="flex gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <p class="text-gray-700 dark:text-gray-300">Aim for 95% confidence level</p>
                </div>
                <div class="flex gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <p class="text-gray-700 dark:text-gray-300">Minimum 100 samples per variant</p>
                </div>
            </div>
        </div>

        <!-- Type Descriptions -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><i class="fas fa-book mr-1"></i>Test Types</h3>
            </div>
            <div class="p-4 space-y-2 text-sm">
                <p class="text-gray-700 dark:text-gray-300"><strong>Content:</strong> Test caption length, tone, or CTAs</p>
                <p class="text-gray-700 dark:text-gray-300"><strong>Timing:</strong> Compare posting times/days</p>
                <p class="text-gray-700 dark:text-gray-300"><strong>Hashtag:</strong> Test different hashtag sets</p>
                <p class="text-gray-700 dark:text-gray-300"><strong>Media:</strong> Compare images vs videos</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('alpine:init', () => {
    Alpine.data('variantBuilder', () => ({
        testType: 'content',
        variantA: '',
        variantB: '',
        typeHint: '',
        typeHints: {
            content: 'Compare different caption text, tones, or calls-to-action.',
            timing: 'Compare posting at different times or days of the week.',
            hashtag: 'Compare different hashtag strategies or sets.',
            media: 'Compare different media types (image vs video, carousel vs single).'
        },
        init() {
            this.updateTypeHints();
        },
        updateTypeHints() {
            this.typeHint = this.typeHints[this.testType] || '';
        },
        updatePreview() {
            // Preview is reactive via Alpine
        }
    }));
});
</script>
@endpush
@endsection
