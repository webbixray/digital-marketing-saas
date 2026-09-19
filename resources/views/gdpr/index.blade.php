@extends('layouts.unified')
@section('title', 'Privacy & Data')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Consent Management</h3></div>
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($consents as $consent)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <td>{{ ucfirst($consent->consent_type) }}</td>
                                <td><span class="px-2 py-1 text-xs font-medium rounded-full {{ $consent->granted ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">{{ $consent->granted ? 'Granted' : 'Denied' }}</span></td>
                                <td>{{ $consent->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="bg-white rounded-xl shadow-md border border-red-200 dark:bg-gray-800 dark:border-red-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Delete My Data</h3></div>
                <div class="p-6">
                    <p class="text-red-600 dark:text-red-400"><strong>Warning:</strong> This action is irreversible. Your account will be permanently deleted after a 30-day cooling period.</p>
                    <form action="{{ route('gdpr.delete') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.')">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason (optional)</label>
                            <textarea name="reason" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3" placeholder="Why are you deleting your account?"></textarea>
                        </div>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors">Request Account Deletion</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
