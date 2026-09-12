@extends('layouts.app')
@section('title', 'Create Template')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Create Template</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <form action="{{ route('content-templates.store') }}" method="POST">
                        @csrf
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Name *</label>
                                    <input type="text" name="name" class="form-control" required placeholder="Template name">
                                </div>
                                <div class="form-group">
                                    <label>Platform *</label>
                                    <select name="platform" class="form-control" required>
                                        @foreach($platforms as $platform)
                                        <option value="{{ $platform }}">{{ ucfirst($platform) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Type *</label>
                                    <select name="type" class="form-control" required>
                                        @foreach($types as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Content *</label>
                                    <textarea name="template_content" class="form-control" rows="10" required placeholder="Use &#123;&#123;variable&#125;&#125; for dynamic content"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Template</button>
                                <a href="{{ route('content-templates.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
