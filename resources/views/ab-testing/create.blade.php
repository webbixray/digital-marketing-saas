@extends('layouts.app')
@section('title', 'Create A/B Test')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Create A/B Test</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ab-testing.index') }}">A/B Testing</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Design Your Test</h3>
                    </div>
                    <form action="{{ route('ab-testing.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name">Test Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Caption Length Test">
                                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type">Test Type <span class="text-danger">*</span></label>
                                        <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                            <option value="content">Content</option>
                                            <option value="timing">Timing</option>
                                            <option value="hashtag">Hashtag</option>
                                            <option value="media">Media</option>
                                        </select>
                                        @error('type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="social_account_id">Social Account <span class="text-danger">*</span></label>
                                        <select class="form-control @error('social_account_id') is-invalid @enderror" id="social_account_id" name="social_account_id" required>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ ucfirst($account->platform) }} - {{ $account->platform_username }}</option>
                                            @endforeach
                                        </select>
                                        @error('social_account_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="platform">Platform <span class="text-danger">*</span></label>
                                        <select class="form-control @error('platform') is-invalid @enderror" id="platform" name="platform" required>
                                            <option value="facebook">Facebook</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="twitter">Twitter</option>
                                            <option value="linkedin">LinkedIn</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="pinterest">Pinterest</option>
                                        </select>
                                        @error('platform')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="hypothesis">Hypothesis (optional)</label>
                                <textarea class="form-control @error('hypothesis') is-invalid @enderror" id="hypothesis" name="hypothesis" rows="2" placeholder="e.g., Shorter captions will get more engagement">{{ old('hypothesis') }}</textarea>
                                @error('hypothesis')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label for="sample_size">Sample Size (per variant) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('sample_size') is-invalid @enderror" id="sample_size" name="sample_size" value="{{ old('sample_size', 100) }}" min="50" max="10000" required>
                                <small class="form-text text-muted">Minimum 50 per variant. Recommended: 100-500 for statistical significance.</small>
                                @error('sample_size')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">Variant A (Control)</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="variant_a_content">Content <span class="text-danger">*</span></label>
                                                <textarea class="form-control @error('variant_a_content') is-invalid @enderror" id="variant_a_content" name="variant_a_content" rows="4" required placeholder="Enter your control variant content...">{{ old('variant_a_content') }}</textarea>
                                                @error('variant_a_content')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-outline card-warning">
                                        <div class="card-header">
                                            <h3 class="card-title">Variant B (Treatment)</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="variant_b_content">Content <span class="text-danger">*</span></label>
                                                <textarea class="form-control @error('variant_b_content') is-invalid @enderror" id="variant_b_content" name="variant_b_content" rows="4" required placeholder="Enter your treatment variant content...">{{ old('variant_b_content') }}</textarea>
                                                @error('variant_b_content')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-flask mr-2"></i>Create Test
                            </button>
                            <a href="{{ route('ab-testing.index') }}" class="btn btn-link">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
