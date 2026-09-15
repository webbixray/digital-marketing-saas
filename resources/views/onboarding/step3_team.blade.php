@extends('layouts.unified')
@section('title', 'Invite Team Members')

@section('content')
<div class="space-y-6">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="card-body">
                    <h5 class="text-center mb-3">Step 3 of 5: Invite Your Team</h5>
                    <div class="progress" style="height: 25px;">
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

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users mr-2"></i>Invite Team Members</h3>
                </div>

                <form action="{{ route('onboarding.step3') }}" method="POST" id="inviteForm">
                    @csrf
                    <div class="card-body">
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
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label>Name</label>
                                            <input type="text" class="form-control"
                                                   name="invites[0][name]" placeholder="Full name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label>Email</label>
                                            <input type="email" class="form-control"
                                                   name="invites[0][email]" placeholder="email@example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label>Role</label>
                                            <select class="form-control" name="invites[0][role]">
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
                        <a href="{{ route('onboarding.step2') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
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
