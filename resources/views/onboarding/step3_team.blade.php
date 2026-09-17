@extends('layouts.unified')
@section('title', 'Invite Team Members')

@section('content')
<x-flash-messages />
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-users text-indigo-600 mr-2"></i>Invite Team Members</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 mb-6">Invite team members to collaborate. You can add more later from agency settings.</p>

            @if($teamMembers->count() > 0)
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Current Team</h4>
                    <div class="space-y-2">
                        @foreach($teamMembers as $member)
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-3">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $member->email }}</span>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $member->role === 'admin' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                    {{ ucfirst($member->role) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Send Invitations</h4>
            <form action="{{ route('onboarding.step3') }}" method="POST" id="inviteForm">
                @csrf
                <div class="space-y-3" id="inviteRows">
                    <div class="invite-row bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                <input type="text" name="invites[0][name]" placeholder="Full name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input type="email" name="invites[0][email]" placeholder="email@example.com"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                                <select name="invites[0][role]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-medium" onclick="addInviteRow()">
                    <i class="fas fa-plus mr-1"></i> Add Another
                </button>
            </form>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
            <a href="{{ route('onboarding.step2') }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="{{ route('onboarding.step4') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                Next: Create Campaign <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let rowCount = 1;
    function addInviteRow() {
        const container = document.getElementById('inviteRows');
        const newRow = container.querySelector('.invite-row').cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(inp => {
            inp.name = inp.name.replace('[0]', '[' + rowCount + ']');
            inp.value = '';
        });
        container.appendChild(newRow);
        rowCount++;
    }
</script>
@endpush
