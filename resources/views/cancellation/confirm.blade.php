@extends("layouts.unified")
@section('title', 'Subscription Cancelled')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Subscription Cancelled</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Cancelled</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4 justify-center>
            <div class="col-span-12 lg:col-span-8">
                <div class="card card-outline card-danger">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Your subscription has been cancelled</h3>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-frown fa-4x text-muted mb-4"></i>
                        <h4>We're sorry to see you go!</h4>
                        <p class="text-muted">Your account has been downgraded to the Free plan. You can still use the platform with limited features.</p>
                        <p class="text-muted">Changed your mind? You can reactivate your subscription at any time.</p>

                        <div class="mt-4">
                            <a href="{{ route('agency.billing') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                                <i class="fas fa-undo mr-2"></i>Reactivate Subscription
                            </a>
                            <a href="{{ route('dashboard') }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 inline-flex items-center gap-2 font-medium transition-colors">
                                <i class="fas fa-home mr-2"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
