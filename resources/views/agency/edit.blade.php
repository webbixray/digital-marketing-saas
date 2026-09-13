@extends('layouts.modern')

@section('title', 'Edit Agency')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Agency</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Update your agency information.</p>
    </div>

    <form method="POST" action="{{ route('agency.update') }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Agency Name</label>
                    <input type="text" name="name" value="{{ old('name', $agency->name) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Website</label>
                    <input type="url" name="website" value="{{ old('website', $agency->website) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input">{{ old('description', $agency->description) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
@endsection
