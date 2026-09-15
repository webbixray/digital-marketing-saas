@extends('layouts.unified')
@section('title', 'Edit Landing Page')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Landing Page</h3></div>
    <form action="{{ route('landing-pages.update', $page) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $page->name }}" required></div>
            <div class="form-group"><label>Headline</label><input type="text" name="headline" class="form-control" value="{{ $page->headline }}"></div>
            <div class="form-group"><label>Content</label><textarea name="content" class="form-control" rows="6">{{ $page->content }}</textarea></div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>CTA Text</label><input type="text" name="cta_text" class="form-control" value="{{ $page->cta_text }}">
                <div class="col-md-6"><div class="form-group"><label>CTA URL</label><input type="url" name="cta_url" class="form-control" value="{{ $page->cta_url }}">
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Background</label><input type="color" name="background_color" class="form-control" value="{{ $page->background_color }}">
                <div class="col-md-3"><div class="form-group"><label>Text Color</label><input type="color" name="text_color" class="form-control" value="{{ $page->text_color }}">
                <div class="col-md-3"><div class="form-group"><label>Button Color</label><input type="color" name="button_color" class="form-control" value="{{ $page->button_color }}">
                <div class="col-md-3"><div class="form-group"><label>Button Text</label><input type="color" name="button_text_color" class="form-control" value="{{ $page->button_text_color }}">
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('landing-pages.show', $page) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

