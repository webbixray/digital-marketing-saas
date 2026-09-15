@extends("layouts.unified")
@section('title', 'Create Support Ticket')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Create Support Ticket</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">How can we help?</h3>
                    </div>
                    <form action="{{ route('support.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="subject">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                                @error('subject')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category">Category <span class="text-danger">*</span></label>
                                        <select class="form-control @error('category') is-invalid @enderror" id="category" name="category" required>
                                            <option value="billing">Billing Question</option>
                                            <option value="technical">Technical Issue</option>
                                            <option value="feature_request">Feature Request</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="priority">Priority <span class="text-danger">*</span></label>
                                        <select class="form-control @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                            <option value="low">Low - General inquiry</option>
                                            <option value="medium" selected>Medium - Need help soon</option>
                                            <option value="high">High - Urgent issue</option>
                                            <option value="urgent">Urgent - System down</option>
                                        </select>
                                        @error('priority')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="description">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6" required placeholder="Please describe your issue in detail...">{{ old('description') }}</textarea>
                                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
                            </button>
                            <a href="{{ route('support.index') }}" class="btn btn-link">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
