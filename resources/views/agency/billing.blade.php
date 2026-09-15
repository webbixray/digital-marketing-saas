@extends('layouts.unified')
@section('title', 'Billing')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
            </div>

            <!-- Plans -->
            <div class="row">
                @foreach($plans as $key => $plan)
                <div class="col-md-3">
                    <div class="card {{ ($agency->subscription_plan ?? 'free') === $key ? 'card-primary' : '' }}">
                        <div class="card-header text-center">
                            <h4>{{ $plan['name'] }}</h4>
                            <h2>
                                @if($plan['price'] === 0)
                                    Free
                                @else
                                    ${{ $plan['price'] }}<small>/mo</small>
                                @endif
                            </h2>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> {{ $plan['features']['posts_per_month'] == -1 ? 'Unlimited' : $plan['features']['posts_per_month'] }} posts/month</li>
                                <li><i class="fas fa-check text-success"></i> {{ $plan['features']['ai_generations_per_month'] == -1 ? 'Unlimited' : $plan['features']['ai_generations_per_month'] }} AI generations</li>
                                <li><i class="fas fa-check text-success"></i> {{ $plan['features']['social_accounts'] == -1 ? 'Unlimited' : $plan['features']['social_accounts'] }} social accounts</li>
                                <li><i class="fas fa-check text-success"></i> {{ $plan['features']['team_members'] == -1 ? 'Unlimited' : $plan['features']['team_members'] }} team members</li>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            @if(($agency->subscription_plan ?? 'free') === $key)
                                <button class="btn btn-secondary" disabled>Current Plan</button>
                            @elseif($plan['price'] === 0)
                                <a href="{{ route('billing.checkout', $key) }}" class="btn btn-outline-primary">Downgrade</a>
                            @else
                                <a href="{{ route('billing.checkout', $key) }}" class="btn btn-primary">Upgrade</a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Invoices -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoices</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $inv)
                                    <tr>
                                        <td>{{ $inv->invoice_number }}</td>
                                        <td>{{ $inv->created_at->format('M d, Y') }}</td>
                                        <td>${{ number_format($inv->total, 2) }}</td>
                                        <td><span class="badge badge-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'overdue' ? 'danger' : 'warning') }}">{{ ucfirst($inv->status) }}</span></td>
                                        <td><a href="{{ route('billing.invoice.download', $inv) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center">No invoices yet</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">{{ $invoices->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

