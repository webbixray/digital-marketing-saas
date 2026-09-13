@extends('layouts.modern')

@section('title', 'Role Audit Log')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Role Audit Trail</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">History of role and permission changes.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
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
                                <span class="badge {{ str_contains($log->action ?? '', 'create') ? 'badge-success' : (str_contains($log->action ?? '', 'delete') ? 'badge-danger' : 'badge-info') }}">
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
            </table>
        </div>
    </div>
@endsection
