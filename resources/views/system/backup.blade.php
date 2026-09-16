@extends('layouts.unified')
@section('title', 'Backup Management')

@section('content')
<div class="space-y-6">

</div>


        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">System Backups</h3>
                        <div class="card-tools">
                            <form action="{{ route('system.backup.store') }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
                                    <i class="fas fa-plus"></i> Create Backup
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="p-6">
                        @if(session('success'))
                            <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg p-4 mb-4">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4 mb-4">{{ session('error') }}</div>
                        @endif

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
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
                        </table></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

