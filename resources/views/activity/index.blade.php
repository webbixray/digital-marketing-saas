@extends('layouts.unified')
@section('title', 'Activity Logs')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <span class="text-gray-900 dark:text-white">Activity Logs</span>
    </nav>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Activity Feed</h3>
        </div>
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <form method="GET" action="{{ route('activity.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" id="user_id" class="form-input">
                        <option value="">All Users</option>
                        @foreach($users ?? [] as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="form-label">From</label>
                    <input type="date" name="date_from" id="date_from" class="form-input" value="{{ request('date_from') }}">
                </div>
                <div>
                    <label for="date_to" class="form-label">To</label>
                    <input type="date" name="date_to" id="date_to" class="form-input" value="{{ request('date_to') }}">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('activity.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Reset</a>
                </div>
            </form>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-3 px-4">Time</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($activities ?? [] as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $activity->created_at?->format('M d, Y H:i') }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ substr($activity->user?->name ?? 'S', 0, 1) }}</div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $activity->user?->name ?? 'System' }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="badge badge-{{ str_contains($activity->action, 'delete') ? 'red' : (str_contains($activity->action, 'create') ? 'green' : 'blue') }}">{{ $activity->action }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $activity->details ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-500 dark:text-gray-400">No activity records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(isset($activities) && method_exists($activities, 'links'))
                <div class="mt-4">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
