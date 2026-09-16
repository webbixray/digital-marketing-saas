@extends('layouts.unified')
@section('title', 'Invite Team Members')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-12 gap-4 justify-center>
        <div class="col-span-12 lg:col-span-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="p-6">
                    <h5 class="text-center mb-3">Step 3 of 5: Invite Your Team</h5>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700" style="height: 25px;">
                        <div class="progress-bar bg-primary progress-bar-striped" role="progressbar" style="width: 60%;">
                            60%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-sm text-muted">
                        <span><i class="fas fa-check text-success"></i> Agency Info</span>
                        <span><i class="fas fa-check text-success"></i> Social</span>
                        <span class="font-weight-bold text-primary">Team</span>
                        <span>Campaign</span>
                        <span>AI</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-users mr-2"></i>Invite Team Members</h3>
                </div>

                <form action="{{ route('onboarding.step3') }}" method="POST" id="inviteForm">
                    @csrf
                    <div class="p-6">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-check mr-1"></i>{{ session('success') }}
                            </div>
                        @endif

                        <p class="text-muted">Invite team members to collaborate. You can add more later from agency settings.</p>

                        <!-- Current Team -->
                        @if($teamMembers->count() > 0)
                            <div class="mb-4">
                                <h5>Current Team</h5>
                                <ul class="list-group">
                                    @foreach($teamMembers as $member)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $member->name }}</strong>
                                                <small class="text-muted ml-2">{{ $member->email }}</small>
                                            </div>
                                            <span class="badge badge-{{ $member->role === 'admin' ? 'warning' : 'info' }}">
                                                {{ ucfirst($member->role) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Invite New Members -->
                        <h5>Send Invitations</h5>
                        <div id="inviteRows">
                            <div class="invite-row border rounded p-3 mb-3">
                                <div class="grid grid-cols-12 gap-4>
                                    <div class="col-span-12 md:col-span-4">
                                        <div class="form-group mb-0">
                                            <label>Name</label>
                                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                   name="invites[0][name]" placeholder="Full name">
                                        </div>
                                    </div>
                                    <div class="col-span-12 md:col-span-4">
                                        <div class="form-group mb-0">
                                            <label>Email</label>
                                            <input type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                   name="invites[0][email]" placeholder="email@example.com">
                                        </div>
                                    </div>
                                    <div class="col-span-12 md:col-span-3">
                                        <div class="form-group mb-0">
                                            <label>Role</label>
                                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="invites[0][role]">
                                                <option value="member">Member</option>
                                                <option value="editor">Editor</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm remove-row mb-3" style="display:none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm" id="addInviteRow">
                            <i class="fas fa-plus mr-1"></i> Add Another
                        </button>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('onboarding.step2') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                            Next: Create Campaign <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var rowCount = 1;
        document.getElementById('addInviteRow').addEventListener('click', function() {
            var newRow = document.querySelector('.invite-row').cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(function(inp) {
                inp.name = inp.name.replace('[0]', '[' + rowCount + ']');
                inp.value = '';
            });
            newRow.querySelector('.remove-row').style.display = '';
            document.getElementById('inviteRows').appendChild(newRow);
            rowCount++;
        });
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row')) {
                e.target.closest('.invite-row').remove();
            }
        });
    });
</script>
@endpush
