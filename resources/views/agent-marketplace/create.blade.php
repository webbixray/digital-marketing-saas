@extends('layouts.unified')
@section('title', 'Submit Agent')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="hover:text-gray-700"><a href="{{ route('agent-marketplace.index') }}">Marketplace</a></li>
 <li class="text-gray-900 font-medium">Submit Agent</li>
@endsection

@section('content')
<x-flash-messages />
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Submit New Agent</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">List your AI agent in our marketplace for agencies to discover and install.</p>
    </div>

    <form method="POST" action="{{ route('agent-marketplace.store') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6 space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agent Name *</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug *</label>
            <input type="text" name="slug" value="{{ old('slug') }}" required pattern="[a-z0-9-]+" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lowercase letters, numbers, and hyphens only.</p>
            @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
            <textarea name="description" rows="4" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">{{ old('description') }}</textarea>
            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
            <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                <option value="">Select a category</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (comma-separated)</label>
            <input type="text" name="tags" value="{{ old('tags') }}" placeholder="writing, seo, content, blog" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
            @error('tags')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Class</label>
                <input type="text" name="icon" value="{{ old('icon', 'fas fa-robot') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pricing Type *</label>
                <select name="pricing_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="free" {{ old('pricing_type') === 'free' ? 'selected' : '' }}>Free</option>
                    <option value="paid" {{ old('pricing_type') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pricing_tiers" {{ old('pricing_type') === 'pricing_tiers' ? 'selected' : '' }}>Tiered</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Demo URL</label>
            <input type="url" name="demo_url" value="{{ old('demo_url') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Features (one per line)</label>
            <textarea name="features" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">{{ old('features') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Requirements (one per line)</label>
            <textarea name="requirements" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">{{ old('requirements') }}</textarea>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('agent-marketplace.index') }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Marketplace
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                <i class="fas fa-paper-plane mr-1"></i> Submit for Review
            </button>
        </div>
    </form>
</div>
@endsection
