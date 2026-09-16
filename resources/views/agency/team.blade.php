@extends('layouts.unified')
@section('title', 'Team Members')
@section('content')
<div class="space-y-6">
<div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-user-friends mr-2"></i>Team Members</h3>
        <div class="card-tools"><button class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm" data-toggle="modal" data-target="#inviteModal"><i class="fas fa-user-plus mr-1"></i> Invite</button></div>
    </div>
    <div class="card-body p-0">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Active</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td><span class="badge badge-{{ $member->role === 'owner' ? 'primary' : ($member->role === 'admin' ? 'info' : 'secondary') }}">{{ ucfirst($member->role) }}</span></td>
                        <td><span class="badge badge-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $member->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                        <td>
                            @if($member->id !== $user->id && !$member->isOwner())
                                <form action="{{ route('agency.team.role', $member) }}" method="POST" class="d-inline">
                                    @csrf
                                    <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white form-control-sm d-inline w-auto" onchange="this.form.submit()">
                                        @foreach(['owner','admin','manager','manager','member'] as $r)
                                            <option value="{{ $r }}" {{ $member->role === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <form action="{{ route('agency.team.remove', $member) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Remove?')"><i class="fas fa-user-minus"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No members</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
<!-- Invite Modal -->
<div class="modal fade" id="inviteModal">
    <div class="modal-dialog">
        <form action="{{ route('agency.team.invite') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title">Invite Team Member</h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="mb-4"><label>Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></div>
                    <div class="mb-4"><label>Email</label><input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></div>
                    <div class="mb-4"><label>Role</label><select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><option value="admin">Admin</option><option value="manager">Manager</option><option value="member">Member</option></select></div>
                </div>
                <div class="modal-footer"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Send Invite</button></div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

