@extends('layouts.public-unified')

@section('title', 'Pricing - DigitalMarketingSaaS')

@section('content')
<x-flash-messages />
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Simple, Transparent Pricing</h1>
        <p class="text-xl opacity-90">Choose the plan that fits your needs. All plans include a 14-day free trial.</p>
    </div>
</section>

<section class="py-20 bg-white -mt-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['name' => 'Free', 'price' => '$0', 'desc' => 'Perfect for trying out the platform', 'features' => ['1 User', '10 Posts/month', '5 AI Generations', '2 Social Accounts'], 'missing' => ['Advanced Analytics', 'Workflow Automation'], 'route' => 'register', 'primary' => false],
                ['name' => 'Starter', 'price' => '$29', 'desc' => 'Great for small businesses', 'features' => ['3 Users', '100 Posts/month', '50 AI Generations', '5 Social Accounts', 'Basic Analytics'], 'missing' => ['Workflow Automation'], 'route' => 'register', 'primary' => false],
                ['name' => 'Pro', 'price' => '$79', 'desc' => 'For growing agencies', 'features' => ['10 Users', '500 Posts/month', '200 AI Generations', '15 Social Accounts', 'Advanced Analytics', 'Workflow Automation'], 'missing' => [], 'route' => 'register', 'primary' => true],
                ['name' => 'Enterprise', 'price' => '$199', 'desc' => 'For large organizations', 'features' => ['Unlimited Users', 'Unlimited Posts', 'Unlimited AI', 'Unlimited Accounts', 'Custom Reports', 'Custom Workflows'], 'missing' => [], 'route' => 'public.contact', 'primary' => false],
            ] as $plan)
            <div class="bg-white rounded-xl shadow-md p-6 border-2 {{ $plan['primary'] ? 'border-indigo-600 ring-4 ring-indigo-100' : 'border-gray-200' }}">
                @if($plan['primary'])
                    <span class="bg-indigo-600 text-white text-xs px-3 py-1 rounded-full">Most Popular</span>
                @endif
                <h3 class="text-xl font-bold text-gray-900 mt-4">{{ $plan['name'] }}</h3>
                <div class="my-4">
                    <span class="text-4xl font-extrabold text-gray-900">{{ $plan['price'] }}</span>
                    <span class="text-gray-500">/mo</span>
                </div>
                <p class="text-gray-600 mb-4">{{ $plan['desc'] }}</p>
                <hr class="my-4">
                <ul class="space-y-2 mb-6">
                    @foreach($plan['features'] as $feature)
                        <li class="flex items-center gap-2 text-gray-700"><i class="fas fa-check text-green-500"></i> {{ $feature }}</li>
                    @endforeach
                    @foreach($plan['missing'] as $feature)
                        <li class="flex items-center gap-2 text-gray-400"><i class="fas fa-minus"></i> {{ $feature }}</li>
                    @endforeach
                </ul>
                <a href="{{ route($plan['route']) }}" class="w-full block text-center py-2 px-4 rounded-lg font-semibold {{ $plan['primary'] ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }}">{{ $plan['route'] === 'public.contact' ? 'Contact Sales' : 'Get Started' }}</a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Frequently Asked Questions</h2>
        <div class="space-y-4">
            @foreach([
                ['q' => 'Can I cancel anytime?', 'a' => 'Yes, you can cancel your subscription at any time. No long-term contracts or cancellation fees.'],
                ['q' => 'Is there a free trial?', 'a' => 'All paid plans come with a 14-day free trial. No credit card required to start.'],
                ['q' => 'What payment methods do you accept?', 'a' => 'We accept all major credit cards (Visa, Mastercard, American Express) and PayPal. Enterprise plans can pay via invoice.'],
                ['q' => 'Do you offer refunds?', 'a' => 'Yes! We offer a 14-day money-back guarantee on all annual plans. If you\'re not satisfied, contact us for a full refund.'],
            ] as $faq)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h4 class="font-semibold text-gray-900 mb-2">{{ $faq['q'] }}</h4>
                <p class="text-gray-600">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-gray-500 mb-6">Trusted by agencies worldwide</p>
        <div class="flex flex-wrap gap-6 justify-center">
            <span class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg"><i class="fas fa-shield-alt text-green-500 mr-2"></i>SSL Secured</span>
            <span class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg"><i class="fas fa-lock text-indigo-500 mr-2"></i>GDPR Compliant</span>
            <span class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg"><i class="fas fa-server text-blue-500 mr-2"></i>99.9% Uptime</span>
            <span class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg"><i class="fas fa-undo text-yellow-500 mr-2"></i>14-Day Money-Back</span>
        </div>
    </div>
</section>
@endsection
