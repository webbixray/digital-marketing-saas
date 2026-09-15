@extends('layouts.unified')
@section('title', 'Team Members')
@section('content')
<div class="space-y-6">
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-friends mr-2"></i>Team Members</h3>
        <div class="card-tools"><button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#inviteModal"><i class="fas fa-user-plus mr-1"></i> Invite</button></div>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
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
                                    <select name="role" class="form-control form-control-sm d-inline w-auto" onchange="this.form.submit()">
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
        </table>
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
                    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="form-group"><label>Role</label><select name="role" class="form-control"><option value="admin">Admin</option><option value="manager">Manager</option><option value="member">Member</option></select></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Send Invite</button></div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

