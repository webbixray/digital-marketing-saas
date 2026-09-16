@extends("layouts.unified")

@section('title', 'Clients')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Clients</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your agency clients.</p>
        </div>
        <a href="{{ route('clients.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-plus"></i> Add Client
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mb-6">
        <div class="p-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="lead" {{ request('status') === 'lead' ? 'selected' : '' }}>Lead</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" class="form-input" placeholder="Search clients..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="table-responsive">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Campaigns</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($client->name) }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full" alt="">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $client->name }}</span>
                                </div>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $client->email }}</td>
                            <td>
                                <span class="badge {{ $client->status === 'active' ? 'badge-success' : ($client->status === 'lead' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($client->status ?? 'lead') }}
                                </span>
                            </td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $client->campaigns_count ?? 0 }}</td>
                            <td class="text-gray-500 dark:text-gray-400">{{ $client->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-secondary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="fas fa-users text-4xl mb-4 block"></i>
                                No clients found. <a href="{{ route('clients.create') }}" class="text-indigo-600 hover:text-indigo-700">Add your first client</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        @if($clients->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection
