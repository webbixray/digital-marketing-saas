@extends('layouts.unified')
@section('title', 'Create Landing Page')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Create Landing Page</h3></div>
    <form action="{{ route('landing-pages.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Headline</label><input type="text" name="headline" class="form-control" placeholder="Your compelling headline"></div>
            <div class="form-group"><label>Content</label><textarea name="content" class="form-control" rows="6" placeholder="Page content (HTML supported)"></textarea></div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>CTA Text</label><input type="text" name="cta_text" class="form-control" placeholder="Get Started">
                <div class="col-md-6"><div class="form-group"><label>CTA URL</label><input type="url" name="cta_url" class="form-control" placeholder="https://...">
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Background</label><input type="color" name="background_color" class="form-control" value="#ffffff">
                <div class="col-md-3"><div class="form-group"><label>Text Color</label><input type="color" name="text_color" class="form-control" value="#333333">
                <div class="col-md-3"><div class="form-group"><label>Button Color</label><input type="color" name="button_color" class="form-control" value="#007bff">
                <div class="col-md-3"><div class="form-group"><label>Button Text</label><input type="color" name="button_text_color" class="form-control" value="#ffffff">
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('landing-pages.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

