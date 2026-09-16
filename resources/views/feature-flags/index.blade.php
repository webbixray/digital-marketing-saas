@extends('layouts.unified')
@section('title', 'Feature Flags')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    <div class="content">
        
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50">
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Name</th>
                                <th>Enabled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($flags as $flag)
                            <tr>
                                <td><code>{{ $flag->feature_key }}</code></td>
                                <td>{{ $flag->feature_name }}</td>
                                <td>
                                    <span class="badge badge-{{ $flag->enabled ? 'success' : 'secondary' }}">
                                        {{ $flag->enabled ? 'On' : 'Off' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('feature-flags.show', $flag) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('feature-flags.edit', $flag) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('feature-flags.destroy', $flag) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>
                <div class="card-footer">
                    {{ $flags->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

