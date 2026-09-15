@extends('layouts.unified')
@section('title', 'Edit Client')
@section('content')
<div class="space-y-6">
<div class="row"><div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Edit Client</h3></div>
    <form action="{{ route('clients.update', $client) }}" method="POST">@csrf @method('PUT')
        <div class="card-body">
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $client->name }}" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ $client->email }}" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="{{ $client->phone }}"></div>
            <div class="form-group"><label>Company</label><input type="text" name="company" class="form-control" value="{{ $client->company }}"></div>
            <div class="form-group"><label>Industry</label><input type="text" name="industry" class="form-control" value="{{ $client->industry }}"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ $client->notes }}</textarea></div>
            <div class="form-group"><label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ $client->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $client->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="lead" {{ $client->status === 'lead' ? 'selected' : '' }}>Lead</option>
                </select>
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Update</button> <a href="{{ route('clients.show', $client) }}" class="btn btn-default">Cancel</a></div>
    </form>
</div>
</div>
@endsection

