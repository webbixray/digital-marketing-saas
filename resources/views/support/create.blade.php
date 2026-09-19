@extends("layouts.unified")
@section('title', 'Create Support Ticket')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Support Ticket</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Submit a new support request.</p>
    </div>

    <div class="flex justify-center">
        <div class="w-full lg:w-2/3">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">How can we help?</h3>
                </div>
                <form action="{{ route('support.store') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <div class="mb-4">
                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject <span class="text-red-600 dark:text-red-400">*</span></label>
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-red-600 dark:text-red-400">*</span></label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('category') is-invalid @enderror" id="category" name="category" required>
                                    <option value="billing">Billing Question</option>
                                    <option value="technical">Technical Issue</option>
                                    <option value="feature_request">Feature Request</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('category')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority <span class="text-red-600 dark:text-red-400">*</span></label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                    <option value="low">Low - General inquiry</option>
                                    <option value="medium" selected>Medium - Need help soon</option>
                                    <option value="high">High - Urgent issue</option>
                                    <option value="urgent">Urgent - System down</option>
                                </select>
                                @error('priority')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-red-600 dark:text-red-400">*</span></label>
                            <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('description') is-invalid @enderror" id="description" name="description" rows="6" required placeholder="Please describe your issue in detail...">{{ old('description') }}</textarea>
                            @error('description')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
                        </button>
                        <a href="{{ route('support.index') }}" class="text-indigo-600 hover:text-indigo-700 underline font-medium">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
 @endsection
