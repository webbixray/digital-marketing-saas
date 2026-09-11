@extends('layouts.app')
@section('title', 'A/B Testing')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">A/B Testing</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">A/B Testing</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Your A/B Tests</h3>
                        <div class="card-tools">
                            <a href="{{ route('ab-testing.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-1"></i>New Test
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
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
