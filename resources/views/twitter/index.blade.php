@extends("layouts.unified")

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Twitter / X Integration</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Twitter</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fa fa-check"></i> Success!</h5>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fa fa-ban"></i> Error!</h5>
                    {{ session('error') }}
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Connect Twitter Account</h3>
                        </div>
                        <div class="card-body">
                            <p>Connect your Twitter account to enable:</p>
                            <ul>
                                <li>Automated tweet posting</li>
                                <li>Analytics and metrics tracking</li>
                                <li>Timeline monitoring</li>
                                <li>AI-powered content generation</li>
                            </ul>
                            <a href="{{ route('twitter.connect') }}" class="btn btn-primary">
                                <i class="fa fa-twitter"></i> Connect Twitter Account
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Post a Tweet</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('twitter.post') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="account_id">Select Account</label>
                                    <select name="account_id" id="account_id" class="form-control" required>
                                        <option value="">Select a Twitter account</option>
                                        @foreach($twitterAccounts ?? [] as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->platform_display_name }} (@{{ $account->platform_username }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="text">Tweet Text</label>
                                    <textarea name="text" id="text" class="form-control" rows="3" maxlength="280" required placeholder="What's happening?"></textarea>
                                    <small class="form-text text-muted"><span id="char-count">0</span>/280 characters</small>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-paper-plane"></i> Post Tweet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($twitterAccounts) && count($twitterAccounts) > 0)
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Connected Twitter Accounts</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($twitterAccounts as $account)
                                    <tr>
                                        <td>
                                            <i class="fa fa-twitter text-primary"></i>
                                            {{ $account->platform_display_name }}
                                        </td>
                                        <td>
                                            <a href="https://twitter.com/{{ $account->platform_username }}" target="_blank">
                                                @{{ $account->platform_username }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($account->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('twitter.metrics', $account->id) }}" class="btn btn-sm btn-info">
                                                <i class="fa fa-bar-chart"></i> Metrics
                                            </a>
                                            <a href="{{ route('twitter.timeline', $account->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fa fa-list"></i> Timeline
                                            </a>
                                            <form action="{{ route('twitter.disconnect', $account->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="fa fa-trash"></i> Disconnect
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('text').addEventListener('input', function() {
        document.getElementById('char-count').textContent = this.value.length;
    });
</script>
@endpush
