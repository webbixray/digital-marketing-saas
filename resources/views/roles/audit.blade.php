@extends("layouts.unified")

@section('title', 'Role Audit Log')

@section('content')
    <x-flash-messages />
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Role Audit Trail</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">History of role and permission changes.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="table-responsive">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Role</th>
                        <th>User</th>
                        <th>Details</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs ?? [] as $log)
                        <tr>
                            <td>
                                <span class="{{ str_contains($log->action ?? '', 'create') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : (str_contains($log->action ?? '', 'delete') ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300') }} text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    {{ $log->action ?? 'update' }}
                                </span>
                            </td>
                            <td class="font-medium text-gray-900 dark:text-white">{{ $log->role_name ?? 'N/A' }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $log->user_name ?? 'System' }}</td>
                            <td class="text-sm text-gray-500 dark:text-gray-400">{{ $log->details ?? '' }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
@endsection
