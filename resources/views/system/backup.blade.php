@extends('layouts.unified')
@section('title', 'Backup Management')

@section('content')
<div class="space-y-6">

</div>


        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Backups</h3>
                        <div class="card-tools">
                            <form action="{{ route('system.backup.store') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create Backup
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backups as $backup)
                                    <tr>
                                        <td>{{ $backup['filename'] }}</td>
                                        <td>{{ $backup['size'] }}</td>
                                        <td>{{ $backup['date'] }}</td>
                                        <td>
                                            <form action="{{ route('system.backup.restore', $backup['filename']) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm"
                                                        onclick="return confirm('Are you sure you want to restore this backup?')">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No backups found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

