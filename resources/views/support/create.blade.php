@extends("layouts.unified")
@section('title', 'Create Support Ticket')

@section('content')

    
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Create Support Ticket</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="flex gap-2 text-sm text-gray-500">
                    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="hover:text-gray-700"><a href="{{ route('support.index') }}">Support</a></li>
                    <li class="text-gray-900 font-medium">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>


    
        <div class="grid grid-cols-12 gap-4 justify-center>
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">How can we help?</h3>
                    </div>
                    <form action="{{ route('support.store') }}" method="POST">
                        @csrf
                        <div class="p-6">
                            <div class="mb-4">
                                <label for="subject">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                                @error('subject')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>
                            <div class="grid grid-cols-12 gap-4>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="mb-4">
                                        <label for="category">Category <span class="text-danger">*</span></label>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('category') is-invalid @enderror" id="category" name="category" required>
                                            <option value="billing">Billing Question</option>
                                            <option value="technical">Technical Issue</option>
                                            <option value="feature_request">Feature Request</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('category')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="mb-4">
                                        <label for="priority">Priority <span class="text-danger">*</span></label>
                                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                            <option value="low">Low - General inquiry</option>
                                            <option value="medium" selected>Medium - Need help soon</option>
                                            <option value="high">High - Urgent issue</option>
                                            <option value="urgent">Urgent - System down</option>
                                        </select>
                                        @error('priority')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="description">Description <span class="text-danger">*</span></label>
                                <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('description') is-invalid @enderror" id="description" name="description" rows="6" required placeholder="Please describe your issue in detail...">{{ old('description') }}</textarea>
                                @error('description')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="card-footer">
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
</section>
