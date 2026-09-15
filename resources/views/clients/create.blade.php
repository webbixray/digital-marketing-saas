@extends('layouts.unified')
@section('title', 'Create Client')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Create Client</h3></div>
    <form action="{{ route('clients.store') }}" method="POST">@csrf
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
            <div class="form-group"><label>Company</label><input type="text" name="company" class="form-control"></div>
            <div class="form-group"><label>Industry</label><input type="text" name="industry" class="form-control"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Create</button> <a href="{{ route('clients.index') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

