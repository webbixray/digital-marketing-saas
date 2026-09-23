@extends('layouts.unified')

@section('title', $team->name)

@section('content')
<div x-data="teamShow()" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex items-start justify-between mb-8">
        <div>
            <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('teams.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Teams</a>
                <i class="fas fa-chevron-right text-xs mx-2"></i>
                <span class="text-gray-900 dark:text-white font-medium">{{ $team->name }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $team->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $team->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ $team->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @if($team->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $team->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <button @click="showInviteModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-user-plus"></i>
                Invite Member
            </button>
            <a href="{{ route('teams.edit', $team) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-pen"></i>
                Edit
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Members</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $team->users->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Owner</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $team->owner?->name ?? 'Unknown' }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-envelope text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pending Invites</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $invitations->where('accepted_at', null)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Members List -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Team Members</h2>
                </div>
                @if($team->users->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-gray-500 dark:text-gray-400">No members in this team yet. Invite members to get started.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($team->users as $member)
                            <li class="px-6 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ substr($member->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($member->id === $team->owner_id)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                                            Owner
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ ucfirst($member->role ?? 'member') }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Pending Invitations -->
            @if($invitations->where('accepted_at', null)->count() > 0)
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Invitations</h2>
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invitations->where('accepted_at', null) as $invitation)
                            <li class="px-6 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                                        <i class="fas fa-envelope text-yellow-600 dark:text-yellow-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $invitation->email }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Invited by {{ $invitation->invitedBy?->name ?? 'Unknown' }} · Expires {{ $invitation->expires_at?->diffForHumans() ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        {{ ucfirst($invitation->role) }}
                                    </span>
                                    <form action="{{ route('teams.invite.cancel', $invitation->token) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 dark:hover:text-red-300" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button @click="showInviteModal = true"
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                        <i class="fas fa-user-plus"></i>
                        Invite New Member
                    </button>
                    <a href="{{ route('teams.edit', $team) }}"
                       class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-cog"></i>
                        Team Settings
                    </a>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Team Info</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $team->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Last Updated</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $team->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Invite Modal -->
    <div x-show="showInviteModal" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="showInviteModal = false"></div>
            <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Invite Team Member</h3>
                <form action="{{ route('teams.invite', $team) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="invite_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                            <input type="email" name="email" id="invite_email" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="member@example.com">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="invite_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                            <select name="role" id="invite_role" required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                                <option value="viewer">Viewer</option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="showInviteModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function teamShow() {
    return {
        showInviteModal: false,
    }
}
</script>
@endpush
