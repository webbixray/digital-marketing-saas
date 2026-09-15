@extends('layouts.app')
@section('title', 'Search')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-search mr-2"></i>Global Search</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('search.index') }}" method="GET">
            @csrf
            <div class="input-group mb-3">
                <input type="text" name="q" class="form-control" placeholder="Search posts, campaigns, clients, content..." value="{{ $query }}">
                <div class="input-group-append">
                    <select name="type" class="form-control" style="width: auto;">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All</option>
                        <option value="posts" {{ $type === 'posts' ? 'selected' : '' }}>Posts</option>
                        <option value="campaigns" {{ $type === 'campaigns' ? 'selected' : '' }}>Campaigns</option>
                        <option value="clients" {{ $type === 'clients' ? 'selected' : '' }}>Clients</option>
                        <option value="content" {{ $type === 'content' ? 'selected' : '' }}>Content</option>
                        <option value="invoices" {{ $type === 'invoices' ? 'selected' : '' }}>Invoices</option>
                        <option value="workflows" {{ $type === 'workflows' ? 'selected' : '' }}>Workflows</option>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>

        @if(empty($query))
            <div class="text-center text-muted py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <p>Enter a search term to find posts, campaigns, clients, content, invoices, and workflows.</p>
            </div>
        @elseif(empty($results))
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No results found for "{{ $query }}"</p>
            </div>
        @else
            @foreach($results as $type => $items)
                @if($items->count() > 0)
                    <h5 class="mt-3">{{ ucfirst($type) }} ({{ $items->count() }})</h5>
                    <div class="list-group mb-3">
                        @foreach($items as $item)
                            @php
                                $url = '#';
                                if ($type === 'posts') $url = route('social.posts.show', $item);
                                elseif ($type === 'campaigns') $url = route('campaigns.show', $item);
                                elseif ($type === 'clients') $url = route('clients.show', $item);
                                elseif ($type === 'content') $url = route('content.show', $item);
                                elseif ($type === 'invoices') $url = route('invoices.show', $item);
                                elseif ($type === 'workflows') $url = route('workflows.show', $item);
                            @endphp
                            <a href="{{ $url }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $item->name ?? $item->invoice_number ?? Str::limit($item->content ?? $item->description, 50) }}</h6>
                                    <small>{{ $item->created_at->diffForHumans() }}</small>
                                </div>
                                <small class="text-muted">{{ ucfirst($type) }}</small>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>
@endsection
