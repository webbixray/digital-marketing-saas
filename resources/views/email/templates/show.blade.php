@extends('layouts.unified')
@section('title', $template->name)

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><th>Subject</th><td>{{ $template->subject }}</td></tr>
                                <tr><th>Category</th><td>{{ ucfirst($template->category) }}</td></tr>
                            </table>
                            <h5>HTML Content</h5>
                            <div class="card bg-light"><div class="card-body">{{ $template->html_content }}
                            @if($template->plain_text_content)
                                <h5 class="mt-3">Plain Text</h5>
                                <div class="card bg-light"><div class="card-body">{{ $template->plain_text_content }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

