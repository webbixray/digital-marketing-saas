@extends("layouts.unified")
@section('title', 'Step 1: Agency Profile')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-rocket text-primary mr-2"></i>Let's Get You Set Up!</h5>
                        <span class="text-muted">Step 1 of 5</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 20%;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="text-center" style="width: 20%;">
                            <div class="badge badge-primary rounded-circle p-2" style="width: 30px; height: 30px; line-height: 20px;">1</div>
                            <p class="text-primary font-weight-bold mt-1 mb-0">Agency</p>
                        </div>
                        <div class="text-center" style="width: 20%;">
                            <div class="badge badge-secondary rounded-circle p-2" style="width: 30px; height: 30px; line-height: 20px;">2</div>
                            <p class="text-muted mt-1 mb-0">Social</p>
                        </div>
                        <div class="text-center" style="width: 20%;">
                            <div class="badge badge-secondary rounded-circle p-2" style="width: 30px; height: 30px; line-height: 20px;">3</div>
                            <p class="text-muted mt-1 mb-0">Team</p>
                        </div>
                        <div class="text-center" style="width: 20%;">
                            <div class="badge badge-secondary rounded-circle p-2" style="width: 30px; height: 30px; line-height: 20px;">4</div>
                            <p class="text-muted mt-1 mb-0">Campaign</p>
                        </div>
                        <div class="text-center" style="width: 20%;">
                            <div class="badge badge-secondary rounded-circle p-2" style="width: 30px; height: 30px; line-height: 20px;">5</div>
                            <p class="text-muted mt-1 mb-0">AI</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Tell Us About Your Agency</h3>
                </div>

                <form action="{{ route('onboarding.step1') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-building fa-3x text-primary mb-2"></i>
                            <p class="text-muted">This helps us personalize your experience and content suggestions.</p>
                        </div>

                        <div class="form-group">
                            <label for="agency_name" class="font-weight-bold">Agency Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-primary text-white"><i class="fas fa-building"></i></span>
                                </div>
                                <input type="text" class="form-control form-control-lg @error('agency_name') is-invalid @enderror"
                                       id="agency_name" name="agency_name"
                                       value="{{ old('agency_name', $agency->name ?? '') }}"
                                       placeholder="e.g., Acme Marketing Solutions" required>
                                @error('agency_name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="website" class="font-weight-bold">Website</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                </div>
                                <input type="url" class="form-control @error('website') is-invalid @enderror"
                                       id="website" name="website"
                                       value="{{ old('website', $agency->website ?? '') }}"
                                       placeholder="https://youragency.com">
                                @error('website')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Optional - helps us understand your brand</small>
                        </div>

                        <div class="form-group">
                            <label for="timezone" class="font-weight-bold">Timezone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                </div>
                                <select class="form-control @error('timezone') is-invalid @enderror"
                                        id="timezone" name="timezone" required>
                                    <option value="">Select your timezone</option>
                                    @foreach(timezone_identifiers_list() as $tz)
                                        <option value="{{ $tz }}" {{ old('timezone', $agency->timezone ?? config('app.timezone')) === $tz ? 'selected' : '' }}>
                                            {{ $tz }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('timezone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                        <a href="{{ route('dashboard') }}" class="btn btn-link">Skip for now</a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            Next: Connect Social Accounts <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Tip -->
            <div class="card bg-light mt-4">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-lightbulb fa-2x text-warning mr-3"></i>
                    <div>
                        <h6 class="font-weight-bold mb-1">Quick Tip</h6>
                        <p class="mb-0 text-muted">You can always change these settings later in your agency settings page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
