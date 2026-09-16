@extends('layouts.unified')

@section('title', 'Upgrade Plan')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="hover:text-gray-700"><a href="{{ route('agency.billing') }}">Billing</a></li>
    <li class="text-gray-900 font-medium">Upgrade</li>
@endsection

@section('content')
<div class="space-y-6">
<div class="row mb-4">
    <div class="col-12 text-center">
        <h2><i class="fas fa-rocket text-primary mr-2"></i>Choose Your Plan</h2>
        <p class="text-muted">Select the plan that best fits your agency's needs</p>
    </div>
</div>

<div class="grid grid-cols-12 gap-4 justify-center>
    @foreach($plans as $key => $plan)
        @if($key === 'free')
            @continue
        @endif
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card plan-card {{ $key === 'pro' ? 'border-primary shadow-lg' : '' }} {{ $currentPlan === $key ? 'border-success' : '' }}" style="{{ $key === 'pro' ? 'transform: scale(1.05);' : '' }}">
                @if($key === 'pro')
                    <div class="card-header bg-primary text-white text-center">
                        <i class="fas fa-star mr-1"></i>MOST POPULAR
                    </div>
                @endif
                @if($currentPlan === $key)
                    <div class="card-header bg-success text-white text-center">
                        <i class="fas fa-check-circle mr-1"></i>CURRENT PLAN
                    </div>
                @endif
                <div class="card-body text-center">
                    <h4 class="plan-name">{{ $plan['name'] }}</h4>
                    <div class="plan-price">
                        <span class="currency">$</span>
                        <span class="amount">{{ number_format($plan['price'], 0) }}</span>
                        <span class="period">/mo</span>
                    </div>
                    @if(isset($plan['yearly']))
                        <small class="text-muted d-block mt-1">
                            or ${{ number_format($plan['price'] * 10 * 0.9, 0) }}/yr (save 10%)
                        </small>
                    @endif
                </div>
                <ul class="list-group list-group-flush plan-features">
                    @if(isset($plan['features']))
                        @foreach($plan['features'] as $feature => $limit)
                            <li class="list-group-item">
                                <i class="fas fa-check text-success mr-2"></i>
                                {{ $limit == -1 ? '<strong>Unlimited</strong>' : $limit }}
                                {{ ucwords(str_replace('_', ' ', str_replace('_per_month', '', $feature))) }}
                            </li>
                        @endforeach
                    @endif
                </ul>
                <div class="card-footer text-center">
                    @if($currentPlan === $key)
                        <button class="btn btn-block btn-success" disabled>
                            <i class="fas fa-check mr-1"></i>Current Plan
                        </button>
                    @else
                        <a href="{{ route('billing.checkout', $key) }}" class="btn btn-block {{ $key === 'pro' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="fas fa-arrow-circle-up mr-1"></i>
                            {{ $key === $currentPlan ? 'Keep Plan' : 'Upgrade' }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Feature Comparison Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-table text-info mr-2"></i>Full Feature Comparison
                </h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-hover comparison-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Feature</th>
                            @foreach($plans as $key => $plan)
                                <th class="text-center {{ $currentPlan === $key ? 'table-success' : '' }}">
                                    {{ $plan['name'] }}
                                    @if($currentPlan === $key)
                                        <br><span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Current</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Price / Month</strong></td>
                            @foreach($plans as $key => $plan)
                                <td class="text-center {{ $currentPlan === $key ? 'table-success' : '' }}">
                                    ${{ number_format($plan['price'] ?? 0, 0) }}
                                </td>
                            @endforeach
                        </tr>
                        @php
                            $allFeatures = [];
                            foreach($plans as $plan) {
                                if(isset($plan['features'])) {
                                    foreach(array_keys($plan['features']) as $f) {
                                        $allFeatures[$f] = true;
                                    }
                                }
                            }
                            $featureLabels = [
                                'posts_per_month' => 'Social Posts / Month',
                                'ai_generations_per_month' => 'AI Generations / Month',
                                'social_accounts' => 'Social Accounts',
                                'team_members' => 'Team Members',
                                'landing_pages' => 'Landing Pages',
                                'forms' => 'Forms',
                            ];
                        @endphp
                        @foreach($allFeatures as $feature => $v)
                            <tr>
                                <td><strong>{{ $featureLabels[$feature] ?? ucwords(str_replace('_', ' ', $feature)) }}</strong></td>
                                @foreach($plans as $key => $plan)
                                    <td class="text-center {{ $currentPlan === $key ? 'table-success' : '' }}">
                                        @if(isset($plan['features'][$feature]))
                                            @if($plan['features'][$feature] == -1)
                                                <i class="fas fa-infinity text-success"></i>
                                            @else
                                                {{ $plan['features'][$feature] }}
                                            @endif
                                        @else
                                            <i class="fas fa-minus text-muted"></i>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('styles')
<style>
    .plan-card {
        transition: all 0.3s ease;
        border: 2px solid #dee2e6;
    }
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .plan-card.border-primary {
        border-color: #007bff !important;
    }
    .plan-card.border-success {
        border-color: #28a745 !important;
    }
    .plan-price {
        margin: 1rem 0;
    }
    .plan-price .currency {
        font-size: 1.5rem;
        vertical-align: super;
    }
    .plan-price .amount {
        font-size: 2.5rem;
        font-weight: 700;
        color: #007bff;
    }
    .plan-price .period {
        color: #6c757d;
    }
    .plan-features .list-group-item {
        border-left: none;
        border-right: none;
        font-size: 0.9rem;
    }
    .comparison-table th, .comparison-table td {
        vertical-align: middle;
    }
</style>
@endpush
