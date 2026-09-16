@extends("layouts.unified")
@section('title', 'Create A/B Test')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Create A/B Test</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ab-testing.index') }}">A/B Testing</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4 justify-center>
            <div class="col-span-12 lg:col-span-10">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Design Your Test</h3>
                    </div>
                    <form action="{{ route('ab-testing.store') }}" method="POST">
                        @csrf
                        <div class="p-6">
                            <div class="grid grid-cols-12 gap-4>
                                <div class="col-span-12 md:col-span-8">
                                    <div class="mb-4">
                                        <label for="name">Test Name <span class="text-danger">*</span></label>
                                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Caption Length Test">
                                        @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <div class="mb-4">
                                        <label for="type">Test Type <span class="text-danger">*</span></label>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('type') is-invalid @enderror" id="type" name="type" required>
                                            <option value="content">Content</option>
                                            <option value="timing">Timing</option>
                                            <option value="hashtag">Hashtag</option>
                                            <option value="media">Media</option>
                                        </select>
                                        @error('type')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-4>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="mb-4">
                                        <label for="social_account_id">Social Account <span class="text-danger">*</span></label>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('social_account_id') is-invalid @enderror" id="social_account_id" name="social_account_id" required>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ ucfirst($account->platform) }} - {{ $account->platform_username }}</option>
                                            @endforeach
                                        </select>
                                        @error('social_account_id')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="mb-4">
                                        <label for="platform">Platform <span class="text-danger">*</span></label>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('platform') is-invalid @enderror" id="platform" name="platform" required>
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
                            </div>

                            <div class="mb-4">
                                <label for="hypothesis">Hypothesis (optional)</label>
                                <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('hypothesis') is-invalid @enderror" id="hypothesis" name="hypothesis" rows="2" placeholder="e.g., Shorter captions will get more engagement">{{ old('hypothesis') }}</textarea>
                                @error('hypothesis')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="sample_size">Sample Size (per variant) <span class="text-danger">*</span></label>
                                <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('sample_size') is-invalid @enderror" id="sample_size" name="sample_size" value="{{ old('sample_size', 100) }}" min="50" max="10000" required>
                                <small class="form-text text-muted">Minimum 50 per variant. Recommended: 100-500 for statistical significance.</small>
                                @error('sample_size')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>

                            <div class="grid grid-cols-12 gap-4>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">Variant A (Control)</h3>
                                        </div>
                                        <div class="p-6">
                                            <div class="mb-4">
                                                <label for="variant_a_content">Content <span class="text-danger">*</span></label>
                                                <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('variant_a_content') is-invalid @enderror" id="variant_a_content" name="variant_a_content" rows="4" required placeholder="Enter your control variant content...">{{ old('variant_a_content') }}</textarea>
                                                @error('variant_a_content')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-300 dark:bg-gray-800 dark:border-yellow-700">
                                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">Variant B (Treatment)</h3>
                                        </div>
                                        <div class="p-6">
                                            <div class="mb-4">
                                                <label for="variant_b_content">Content <span class="text-danger">*</span></label>
                                                <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('variant_b_content') is-invalid @enderror" id="variant_b_content" name="variant_b_content" rows="4" required placeholder="Enter your treatment variant content...">{{ old('variant_b_content') }}</textarea>
                                                @error('variant_b_content')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                                <i class="fas fa-flask mr-2"></i>Create Test
                            </button>
                            <a href="{{ route('ab-testing.index') }}" class="text-indigo-600 hover:text-indigo-700 underline font-medium">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
