@extends('layouts.unified')
@section('title', 'Privacy & Data')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
            </div>

            <div class="grid grid-cols-12 gap-4>
                <div class="col-span-12">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Consent Management</h3></div>
                        <div class="p-6">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consents as $consent)
                                    <tr>
                                        <td>{{ ucfirst($consent->consent_type) }}</td>
                                        <td><span class="badge badge-{{ $consent->granted ? 'success' : 'danger' }}">{{ $consent->granted ? 'Granted' : 'Denied' }}</span></td>
                                        <td>{{ $consent->created_at->format('M d, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4>
                <div class="col-span-12">
                    <div class="card card-danger">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h3 class="font-semibold text-gray-900 dark:text-white">Delete My Data</h3></div>
                        <div class="p-6">
                            <p class="text-danger"><strong>Warning:</strong> This action is irreversible. Your account will be permanently deleted after a 30-day cooling period.</p>
                            <form action="{{ route('gdpr.delete') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.')">
                                @csrf
                                <div class="mb-4">
                                    <label>Reason (optional)</label>
                                    <textarea name="reason" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3" placeholder="Why are you deleting your account?"></textarea>
                                </div>
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 inline-flex items-center gap-2 font-medium transition-colors">Request Account Deletion</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

