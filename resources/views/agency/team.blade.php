@extends('layouts.unified')
@section('title', 'Team Members')
@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Team Members</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your agency team members and their roles.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-user-friends mr-2"></i>Team Members</h3>
            <button class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm" onclick="document.getElementById('inviteModal').showModal()"><i class="fas fa-user-plus mr-1"></i> Invite</button>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Active</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($members as $member)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $member->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $roleColor = $member->role === 'owner' ? 'indigo' : ($member->role === 'admin' ? 'blue' : 'gray');
                                @endphp
                                <span class="{{ $roleColor === 'indigo' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300' : ($roleColor === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($member->role) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ $member->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $member->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3">
                                @if($member->id !== $user->id && !$member->isOwner())
                                    <div class="flex items-center gap-2">
                                    <form action="{{ route('agency.team.role', $member) }}" method="POST" class="inline">
                                        @csrf
                                        <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" onchange="this.form.submit()">
                                            @foreach(['owner','admin','manager','manager','member'] as $r)
                                                <option value="{{ $r }}" {{ $member->role === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form action="{{ route('agency.team.remove', $member) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded-lg hover:bg-red-700 text-sm font-medium transition-colors" onclick="return confirm('Remove?')"><i class="fas fa-user-minus"></i></button>
                                    </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No members</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Invite Modal -->
<dialog id="inviteModal" class="p-0 rounded-xl shadow-xl dark:bg-gray-800 backdrop:bg-black/50">
    <div class="w-full max-w-md">
        <form action="{{ route('agency.team.invite') }}" method="POST">
            @csrf
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h4 class="font-semibold text-gray-900 dark:text-white">Invite Team Member</h4>
                <button type="button" onclick="document.getElementById('inviteModal').close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label><input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label><select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><option value="admin">Admin</option><option value="manager">Manager</option><option value="member">Member</option></select></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"><button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Send Invite</button></div>
        </form>
    </div>
</dialog>
 @endsection
