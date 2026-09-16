@extends("layouts.unified")
@section('title', 'A/B Testing')

@section('content')

    
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">A/B Testing</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="flex gap-2 text-sm text-gray-500">
                    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="text-gray-900 font-medium">A/B Testing</li>
                </ol>
            </div>
        </div>
    </div>
</div>


    
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Your A/B Tests</h3>
                        <div class="card-tools">
                            <a href="{{ route('ab-testing.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
                                <i class="fas fa-plus mr-1"></i>New Test
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Platform</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Winner</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tests as $test)
                                    <tr>
                                        <td><a href="{{ route('ab-testing.show', $test) }}">{{ $test->name }}</a></td>
                                        <td>{{ ucfirst($test->platform) }}</td>
                                        <td>{{ ucfirst($test->type) }}</td>
                                        <td><span class="badge badge-{{ $test->status === 'running' ? 'success' : ($test->status === 'completed' ? 'primary' : 'secondary') }}">{{ ucfirst($test->status) }}</span></td>
                                        <td>
                                            @if($test->winner)
                                                <span class="badge badge-{{ $test->winner === 'inconclusive' ? 'warning' : 'success' }}">{{ ucfirst($test->winner) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $test->created_at->toDateString() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No A/B tests found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $tests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
