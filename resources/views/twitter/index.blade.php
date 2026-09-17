@extends("layouts.unified")

@section('content')
<x-flash-messages />
<div class="content-wrapper">
    
            <div class="grid grid-cols-12 gap-4 mb-2">
                <div class="col-span-12 sm:col-span-6">
                    <h1 class="m-0">Twitter / X Integration</h1>
                </div>
                <div class="col-span-12 sm:col-span-6">
                    <ol class="flex gap-2 text-sm text-gray-500">
                        <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="text-gray-900 font-medium">Twitter</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        
            @if(session('success'))
                <div class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 rounded-xl p-4 mb-4 flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="text-green-700 dark:text-green-300 hover:opacity-70 ml-4" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4 flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-ban"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" class="text-red-700 dark:text-red-300 hover:opacity-70 ml-4" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-6">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Connect Twitter Account</h3>
                        </div>
                        <div class="p-6">
                            <p>Connect your Twitter account to enable:</p>
                            <ul>
                                <li>Automated tweet posting</li>
                                <li>Analytics and metrics tracking</li>
                                <li>Timeline monitoring</li>
                                <li>AI-powered content generation</li>
                            </ul>
                            <a href="{{ route('twitter.connect') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                                <i class="fa fa-twitter"></i> Connect Twitter Account
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-6">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Post a Tweet</h3>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('twitter.post') }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="account_id">Select Account</label>
                                    <select name="account_id" id="account_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="">Select a Twitter account</option>
                                        @foreach($twitterAccounts ?? [] as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->platform_display_name }} (@{{ $account->platform_username }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="text">Tweet Text</label>
                                    <textarea name="text" id="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3" maxlength="280" required placeholder="What's happening?"></textarea>
                                    <small class="text-sm text-gray-500 dark:text-gray-400"><span id="char-count">0</span>/280 characters</small>
                                </div>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors">
                                    <i class="fa fa-paper-plane"></i> Post Tweet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($twitterAccounts) && count($twitterAccounts) > 0)
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Connected Twitter Accounts</h3>
                        </div>
                        <div class="p-0 overflow-x-auto">
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="w-full text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($twitterAccounts as $account)
                                    <tr>
                                        <td>
                                            <i class="fa fa-twitter text-indigo-600 dark:text-indigo-400"></i>
                                            {{ $account->platform_display_name }}
                                        </td>
                                        <td>
                                            <a href="https://twitter.com/{{ $account->platform_username }}" target="_blank">
                                                @{{ $account->platform_username }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($account->is_active)
                                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Active</span>
                                            @else
                                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('twitter.metrics', $account->id) }}" class="bg-blue-600 text-white px-3 py-1 text-sm rounded-lg hover:bg-blue-700 inline-flex items-center gap-1 font-medium transition-colors">
                                                <i class="fa fa-bar-chart"></i> Metrics
                                            </a>
                                            <a href="{{ route('twitter.timeline', $account->id) }}" class="bg-indigo-600 text-white px-3 py-1 text-sm rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors">
                                                <i class="fa fa-list"></i> Timeline
                                            </a>
                                            <form action="{{ route('twitter.disconnect', $account->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-600 text-white px-3 py-1 text-sm rounded-lg hover:bg-red-700 inline-flex items-center gap-1 font-medium transition-colors" onclick="return confirm('Are you sure?')">
                                                    <i class="fa fa-trash"></i> Disconnect
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        dmsaas.initCharCounter('#text', '#char-count');
    });
</script>
@endpush
