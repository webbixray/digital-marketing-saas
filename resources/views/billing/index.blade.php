@extends('layouts.unified')

@section('title', 'Billing & Subscription')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Billing</li>
@endsection

@section('content')
<div class="space-y-6">
<!-- Current Plan -->
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-crown text-warning mr-2"></i>Current Plan
                </h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $agency->subscription_status === 'active' ? 'success' : 'secondary' }} badge-lg">
                        {{ ucfirst($agency->subscription_status ?? 'N/A') }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-primary">{{ $plans[$currentPlan]['name'] ?? 'Free' }}</h4>
                        @if(isset($plans[$currentPlan]['price']))
                            <h2 class="mb-0">${{ number_format($plans[$currentPlan]['price'], 0) }}<small class="text-muted">/mo</small></h2>
                        @else
                            <h2 class="mb-0">Free</h2>
                        @endif
                        @if($agency->subscription_start)
                            <p class="text-muted mt-2 mb-0">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    Started {{ \Carbon\Carbon::parse($agency->subscription_start)->format('M d, Y') }}
                                </small>
                            </p>
                        @endif
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="{{ route('billing.upgrade') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-circle-up mr-1"></i>
                            {{ $currentPlan === 'free' ? 'Upgrade Plan' : 'Change Plan' }}
                        </a>
                        @if($currentPlan !== 'free')
                            <form method="POST" action="{{ route('billing.cancel-subscription') }}" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-lg mt-2">
                                    <i class="fas fa-times-circle mr-1"></i>Cancel Subscription
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Stats -->
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie text-info mr-2"></i>Usage This Month
                </h3>
            </div>
            <div class="card-body">
                @if(isset($plans[$currentPlan]['features']))
                    <div class="row">
                        @if(isset($plans[$currentPlan]['features']['posts_per_month']))
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary"><i class="fas fa-pen-fancy"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Social Posts</span>
                                    <span class="info-box-number">
                                        {{ $agency->posts_count ?? 0 }}
                                        <small>/ {{ $plans[$currentPlan]['features']['posts_per_month'] == -1 ? '∞' : $plans[$currentPlan]['features']['posts_per_month'] }}</small>
                                    </span>
                                    @if($plans[$currentPlan]['features']['posts_per_month'] != -1)
                                        @php
                                            $percent = min(100, (($agency->posts_count ?? 0) / $plans[$currentPlan]['features']['posts_per_month']) * 100);
                                        @endphp
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ round($percent) }}% used</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($plans[$currentPlan]['features']['ai_generations_per_month']))
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="fas fa-sparkles"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">AI Generations</span>
                                    <span class="info-box-number">
                                        {{ $agency->ai_generations_count ?? 0 }}
                                        <small>/ {{ $plans[$currentPlan]['features']['ai_generations_per_month'] == -1 ? '∞' : $plans[$currentPlan]['features']['ai_generations_per_month'] }}</small>
                                    </span>
                                    @if($plans[$currentPlan]['features']['ai_generations_per_month'] != -1)
                                        @php
                                            $percent = min(100, (($agency->ai_generations_count ?? 0) / $plans[$currentPlan]['features']['ai_generations_per_month']) * 100);
                                        @endphp
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ round($percent) }}% used</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($plans[$currentPlan]['features']['team_members']))
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Team Members</span>
                                    <span class="info-box-number">
                                        {{ $agency->users_count ?? 0 }}
                                        <small>/ {{ $plans[$currentPlan]['features']['team_members'] == -1 ? '∞' : $plans[$currentPlan]['features']['team_members'] }}</small>
                                    </span>
                                    @if($plans[$currentPlan]['features']['team_members'] != -1)
                                        @php
                                            $percent = min(100, (($agency->users_count ?? 0) / $plans[$currentPlan]['features']['team_members']) * 100);
                                        @endphp
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ round($percent) }}% used</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted">No usage limits on your current plan.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Plan Summary Sidebar -->
    <div class="col-md-4">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-check text-success mr-2"></i>Plan Features</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @if(isset($plans[$currentPlan]['features']))
                        @foreach($plans[$currentPlan]['features'] as $feature => $limit)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-check text-success mr-2"></i>{{ ucwords(str_replace('_', ' ', $feature)) }}</span>
                                <span class="badge badge-primary badge-pill">{{ $limit == -1 ? 'Unlimited' : $limit }}</span>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt text-warning mr-2"></i>Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('billing.upgrade') }}" class="btn btn-block btn-outline-primary">
                    <i class="fas fa-exchange-alt mr-1"></i> Compare Plans
                </a>
                <a href="{{ route('agency.invoices') }}" class="btn btn-block btn-outline-info mt-2">
                    <i class="fas fa-file-invoice mr-1"></i> View Invoices
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Billing History -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history text-secondary mr-2"></i>Recent Billing History
                </h3>
                <div class="card-tools">
                    <a href="{{ route('agency.invoices') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                @if($invoices->count())
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices->take(5) as $invoice)
                                <tr>
                                    <td><code>{{ $invoice->invoice_number }}</code></td>
                                    <td>{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('M d, Y') : '—' }}</td>
                                    <td><strong>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</strong></td>
                                    <td>
                                        @php
                                            $badgeClass = match(strtolower($invoice->status)) {
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'overdue' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'info'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('billing.invoice.download', $invoice) }}" class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-invoice fa-3x mb-3 d-block"></i>
                        <p>No billing history yet.</p>
                    </div>
                @endif
            </div>
            @if($invoices->count())
                <div class="card-footer">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection
