@extends('layouts.unified')
@section('title', 'Create First Campaign')

@section('content')
<div class="space-y-6">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="card-body">
                    <h5 class="text-center mb-3">Step 4 of 5: Create Your First Campaign</h5>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-primary progress-bar-striped" role="progressbar" style="width: 80%;">
                            80%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-sm text-muted">
                        <span><i class="fas fa-check text-success"></i> Agency Info</span>
                        <span><i class="fas fa-check text-success"></i> Social</span>
                        <span><i class="fas fa-check text-success"></i> Team</span>
                        <span class="font-weight-bold text-primary">Campaign</span>
                        <span>AI</span>
                    </div>
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i>Create Your First Campaign</h3>
                </div>

                <form action="{{ route('onboarding.step4') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-check mr-1"></i>{{ session('success') }}
                            </div>
                        @endif

                        <p class="text-muted">Set up your first marketing campaign. You can always edit it later.</p>

                        <div class="form-group">
                            <label for="name">Campaign Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-bullhorn"></i></span>
                                </div>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}"
                                       placeholder="e.g., Summer Sale 2024" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="type">Campaign Type <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                </div>
                                <select class="form-control @error('type') is-invalid @enderror"
                                        id="type" name="type" required>
                                    <option value="">Select campaign type</option>
                                    @foreach($campaignTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="objective">Objective</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-bullseye"></i></span>
                                </div>
                                <input type="text" class="form-control @error('objective') is-invalid @enderror"
                                       id="objective" name="objective" value="{{ old('objective') }}"
                                       placeholder="e.g., Increase brand awareness by 30%">
                                @error('objective')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="Brief description of your campaign">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        </div>
                                        <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                               id="start_date" name="start_date" value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                        </div>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                               id="end_date" name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('onboarding.step3') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            Next: Activate AI <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

