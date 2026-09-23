@extends('layouts.unified')
@section('title', $item->name)
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="hover:text-gray-700"><a href="{{ route('agent-marketplace.index') }}">Marketplace</a></li>
 <li class="text-gray-900 font-medium">{{ $item->name }}</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col md:flex-row md:items-start gap-6">
            <div class="w-20 h-20 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                <i class="{{ $item->icon ?? 'fas fa-robot' }} text-4xl"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $item->name }}</h2>
                    @if($item->is_featured)
                    <span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 text-xs font-medium px-2.5 py-0.5 rounded-full"><i class="fas fa-star mr-1"></i>Featured</span>
                    @endif
                    <span class="text-xs px-2.5 py-0.5 rounded-full {{ $item->pricing_type === 'free' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' }}">
                        {{ $item->pricing_type === 'free' ? 'Free' : ($item->pricing_type === 'paid' ? 'Paid' : 'Multiple Tiers') }}
                    </span>
                </div>
                <div class="flex items-center gap-4 mb-3 text-sm">
                    <span class="text-indigo-600 dark:text-indigo-400"><i class="fas fa-folder mr-1"></i>{{ $item->category->name ?? 'Uncategorized' }}</span>
                    <span class="text-gray-500 dark:text-gray-400"><i class="fas fa-star text-yellow-500 mr-1"></i>{{ number_format($item->rating_avg, 1) }} ({{ $item->rating_count }} reviews)</span>
                    <span class="text-gray-500 dark:text-gray-400"><i class="fas fa-download mr-1"></i>{{ number_format($item->install_count) }} installs</span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $item->description }}</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($item->tags ?? [] as $tag)
                    <span class="text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 px-2.5 py-1 rounded-full">{{ $tag }}</span>
                    @endforeach
                </div>
                <!-- Install / Actions -->
                <div class="flex items-center gap-3">
                    @if($installStatus['installed'])
                    <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-check mr-1"></i> Installed
                    </span>
                    <a href="{{ route('agent-marketplace.configure', ['item' => $item->id]) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">
                        <i class="fas fa-cog mr-1"></i> Configure
                    </a>
                    @else
                    <form method="POST" action="{{ route('agent-marketplace.install') }}" class="inline">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                            <i class="fas fa-download mr-1"></i> Install Agent
                        </button>
                    </form>
                    @endif
                    @if($item->demo_url)
                    <a href="{{ $item->demo_url }}" target="_blank" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm font-medium transition-colors dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <i class="fas fa-external-link-alt mr-1"></i> Live Demo
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Screenshots -->
    @if(!empty($item->screenshots))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-images mr-2"></i>Screenshots</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($item->screenshots as $screenshot)
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-700 flex items-center justify-center" style="min-height: 200px;">
                <img src="{{ $screenshot }}" alt="Screenshot" class="w-full h-auto" onerror="this.parentElement.innerHTML='<i class=\'fas fa-image text-gray-300 text-4xl\'></i>'">
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Features & Requirements -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if(!empty($item->features))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-list-check mr-2"></i>Features</h3>
            <ul class="space-y-2">
                @foreach($item->features as $feature)
                <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($item->requirements))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-clipboard-list mr-2"></i>Requirements</h3>
            <ul class="space-y-2">
                @foreach($item->requirements as $req)
                <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    {{ $req }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <!-- Pricing -->
    @if(!empty($item->pricing_config))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-tags mr-2"></i>Pricing</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if(isset($item->pricing_config['free_tier']))
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-center">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Free</h4>
                <p class="text-2xl font-bold text-green-600 mb-2">$0</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->pricing_config['free_tier']['posts_per_month'] ?? 'Limited' }} posts/mo</p>
            </div>
            @endif
            @if(isset($item->pricing_config['pro_tier']))
            <div class="border border-indigo-200 dark:border-indigo-700 rounded-lg p-4 text-center bg-indigo-50 dark:bg-indigo-900/30">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Pro</h4>
                <p class="text-2xl font-bold text-indigo-600 mb-2">${{ $item->pricing_config['pro_tier']['price'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->pricing_config['pro_tier']['posts_per_month'] === -1 ? 'Unlimited' : $item->pricing_config['pro_tier']['posts_per_month'] }} posts/mo</p>
            </div>
            @endif
            @if(isset($item->pricing_config['enterprise_tier']))
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-center">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Enterprise</h4>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-2">${{ $item->pricing_config['enterprise_tier']['price'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->pricing_config['enterprise_tier']['posts_per_month'] === -1 ? 'Unlimited' : $item->pricing_config['enterprise_tier']['posts_per_month'] }} posts/mo</p>
            </div>
            @endif
            @if(isset($item->pricing_config['monthly_price']))
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-center">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Standard</h4>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-2">${{ $item->pricing_config['monthly_price'] }}/mo</p>
                @if(isset($item->pricing_config['annual_price']))
                <p class="text-sm text-gray-500 dark:text-gray-400">or ${{ $item->pricing_config['annual_price'] }}/yr</p>
                @endif
                @if(isset($item->pricing_config['trial_days']))
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $item->pricing_config['trial_days'] }}-day free trial</p>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Reviews -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-comments mr-2"></i>Reviews ({{ $reviewStats['total'] }})</h3>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-yellow-500"><i class="fas fa-star"></i> {{ number_format($reviewStats['average'], 1) }}</span>
                <span class="text-gray-500 dark:text-gray-400">average</span>
            </div>
        </div>

        <!-- Rating Distribution -->
        @if($reviewStats['total'] > 0)
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                @foreach([5, 4, 3, 2, 1] as $star)
                <div class="flex items-center gap-2 text-sm mb-1">
                    <span class="w-8 text-gray-600 dark:text-gray-400">{{ $star }} <i class="fas fa-star text-yellow-500 text-xs"></i></span>
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $reviewStats['total'] > 0 ? (($reviewStats['distribution'][$star] ?? 0) / $reviewStats['total'] * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-8 text-gray-500 dark:text-gray-400 text-right">{{ $reviewStats['distribution'][$star] ?? 0 }}</span>
                </div>
                @endforeach
            </div>
            <div>
                @if($reviewStats['verified_count'] > 0)
                <p class="text-sm text-gray-600 dark:text-gray-400"><i class="fas fa-badge-check text-green-500 mr-1"></i> {{ $reviewStats['verified_count'] }} verified purchase{{ $reviewStats['verified_count'] > 1 ? 's' : '' }}</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Review List -->
        <div class="space-y-4">
            @forelse($reviews as $review)
            <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $review->user->name ?? 'User' }}</span>
                        @if($review->is_verified_purchase)
                        <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2 py-0.5 rounded-full"><i class="fas fa-badge-check mr-1"></i>Verified</span>
                        @endif
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                </div>
                <div class="flex items-center gap-1 text-yellow-500 text-sm mb-2">
                    @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star{{ $i <= $review->rating ? '' : '-regular text-gray-300' }}"></i>
                    @endfor
                </div>
                <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ $review->title }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $review->body }}</p>
                <button class="text-xs text-gray-500 dark:text-gray-400 mt-2 hover:text-indigo-600 dark:hover:text-indigo-400" onclick="markHelpful({{ $review->id }})">
                    <i class="fas fa-thumbs-up mr-1"></i> Helpful ({{ $review->helpful_count }})
                </button>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No reviews yet. Be the first to review this agent!</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function markHelpful(reviewId) {
    fetch(`/api/v1/agent-marketplace/reviews/${reviewId}/helpful`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => { if(d.success) location.reload(); });
}
</script>
@endpush
@endsection
