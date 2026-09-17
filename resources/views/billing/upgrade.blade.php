@extends('layouts.unified')

@section('title', 'Upgrade Plan')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="hover:text-gray-700"><a href="{{ route('agency.billing') }}">Billing</a></li>
    <li class="text-gray-900 font-medium">Upgrade</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="mb-4">
    <div class="col-span-12 text-center">
        <h2><i class="fas fa-rocket text-indigo-600 dark:text-indigo-400 mr-2"></i>Choose Your Plan</h2>
        <p class="text-gray-500 dark:text-gray-400">Select the plan that best fits your agency's needs</p>
    </div>
</div>

<div class="grid grid-cols-12 gap-4 justify-center">
    @foreach($plans as $key => $plan)
        @if($key === 'free')
            @continue
        @endif
        <div class="lg:col-span-3 md:col-span-4 sm:col-span-6 mb-4">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 {{ $key === 'pro' ? 'border-indigo-500 shadow-lg' : '' }} {{ $currentPlan === $key ? 'border-green-500' : '' }} {{ $key === 'pro' ? 'lg:scale-105' : '' }}">
                @if($key === 'pro')
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 text-center bg-indigo-50 dark:bg-indigo-900/20">
                        <i class="fas fa-star mr-1"></i>MOST POPULAR
                    </div>
                @endif
                @if($currentPlan === $key)
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 text-center bg-green-50 dark:bg-green-900/20">
                        <i class="fas fa-check-circle mr-1"></i>CURRENT PLAN
                    </div>
                @endif
                <div class="p-6 text-center">
                    <h4 class="plan-name font-semibold text-gray-900 dark:text-white">{{ $plan['name'] }}</h4>
                    <div class="plan-price">
                        <span class="currency">$</span>
                        <span class="amount">{{ number_format($plan['price'], 0) }}</span>
                        <span class="period">/mo</span>
                    </div>
                    @if(isset($plan['yearly']))
                        <small class="text-gray-500 dark:text-gray-400 block mt-1">
                            or ${{ number_format($plan['price'] * 10 * 0.9, 0) }}/yr (save 10%)
                        </small>
                    @endif
                </div>
                <ul class="space-y-2 p-4">
                    @if(isset($plan['features']))
                        @foreach($plan['features'] as $feature => $limit)
                            <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check text-green-600 dark:text-green-400 mr-2"></i>
                                {{ $limit == -1 ? '<strong>Unlimited</strong>' : $limit }}
                                {{ ucwords(str_replace('_', ' ', str_replace('_per_month', '', $feature))) }}
                            </li>
                        @endforeach
                    @endif
                </ul>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-center">
                    @if($currentPlan === $key)
                        <button class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium transition-colors" disabled>
                            <i class="fas fa-check mr-1"></i>Current Plan
                        </button>
                    @else
                        <a href="{{ route('billing.checkout', $key) }}" class="block {{ $key === 'pro' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-4 py-2 rounded-lg font-medium transition-colors text-center">
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
<div class="mt-4">
    <div class="col-span-12">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-table text-blue-600 dark:text-blue-400 mr-2"></i>Full Feature Comparison
                </h3>
            </div>
            <div class="p-0">
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 comparison-table">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th>Feature</th>
                            @foreach($plans as $key => $plan)
                                <th class="text-center {{ $currentPlan === $key ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
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
                                <td class="text-center {{ $currentPlan === $key ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
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
                                    <td class="text-center {{ $currentPlan === $key ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                        @if(isset($plan['features'][$feature]))
                                            @if($plan['features'][$feature] == -1)
                                                <i class="fas fa-infinity text-green-600 dark:text-green-400"></i>
                                            @else
                                                {{ $plan['features'][$feature] }}
                                            @endif
                                        @else
                                            <i class="fas fa-minus text-gray-500 dark:text-gray-400"></i>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
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
