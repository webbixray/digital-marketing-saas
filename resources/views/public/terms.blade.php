@extends('layouts.public-unified')

@section('title', 'Terms of Service')

@section('content')
<x-flash-messages />
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Terms of Service</h1>
        <p class="text-xl opacity-90">Last updated: {{ date('F j, Y') }}</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose max-w-none">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Acceptance of Terms</h2>
            <p class="text-gray-600 mb-6">By accessing and using DigitalMarketingSaaS, you accept and agree to be bound by the terms and provision of this agreement.</p>

            <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Use License</h2>
            <p class="text-gray-600 mb-6">Permission is granted to temporarily download one copy of the materials on DigitalMarketingSaaS for personal, non-commercial transitory viewing only.</p>

            <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Disclaimer</h2>
            <p class="text-gray-600 mb-6">The materials on DigitalMarketingSaaS are provided on an 'as is' basis. DigitalMarketingSaaS makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

            <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Limitations</h2>
            <p class="text-gray-600 mb-6">In no event shall DigitalMarketingSaaS or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on DigitalMarketingSaaS.</p>

            <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Governing Law</h2>
            <p class="text-gray-600 mb-6">These terms and conditions are governed by and construed in accordance with the laws and you irrevocably submit to the exclusive jurisdiction of the courts in that state or location.</p>
        </div>
    </div>
</section>
@endsection
