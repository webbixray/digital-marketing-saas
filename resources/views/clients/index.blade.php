@extends("layouts.unified")

@section('title', 'Clients')

@section('content')
    <x-flash-messages />
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
                    <label for="client-status-filter" class="sr-only">Filter by Status</label>
                    <select id="client-status-filter" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="lead" {{ request('status') === 'lead' ? 'selected' : '' }}>Lead</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Search clients..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
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
                                <img loading="lazy" src="https://ui-avatars.com/api/?name={{ urlencode($client->name) }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full" alt="">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $client->name }}</span>
                            </div>
                        </td>
                        <td class="text-gray-500 dark:text-gray-400">{{ $client->email }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $client->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : ($client->status === 'lead' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">
                                {{ ucfirst($client->status ?? 'lead') }}
                            </span>
                        </td>
                        <td class="text-gray-500 dark:text-gray-400">{{ $client->campaigns_count ?? 0 }}</td>
                        <td class="text-gray-500 dark:text-gray-400">{{ $client->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('clients.show', $client) }}" class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('clients.edit', $client) }}" class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors" title="Delete" onclick="return confirm('Are you sure?')">
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
        @if($clients->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection