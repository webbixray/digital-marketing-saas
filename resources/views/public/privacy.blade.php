@extends('layouts.public-unified')

@section('title', 'Privacy Policy')

@section('content')
<x-flash-messages />
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Privacy Policy</h1>
        <p class="text-xl opacity-90">Last updated: {{ date('F j, Y') }}</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Information We Collect</h2>
        <p class="text-gray-600 mb-6">We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support.</p>

        <h2 class="text-2xl font-bold text-gray-900 mb-4">2. How We Use Your Information</h2>
        <p class="text-gray-600 mb-6">We use the information we collect to provide, maintain, and improve our services, to process transactions, and to send you communications.</p>

        <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Information Sharing</h2>
        <p class="text-gray-600 mb-6">We do not share your personal information with third parties except as necessary to provide our services or as required by law.</p>

        <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Data Security</h2>
        <p class="text-gray-600 mb-6">We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

        <h2 class="text-2xl font-bold text-gray-900 mb-4">5. GDPR Compliance</h2>
        <p class="text-gray-600 mb-6">We are committed to complying with the General Data Protection Regulation (GDPR). You have the right to access, correct, or delete your personal data.</p>
    </div>
</section>
@endsection
