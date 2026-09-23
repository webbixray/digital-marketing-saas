@extends('layouts.unified')
@section('title', 'AI Provider Management')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .provider-card {
        transition: all 0.3s ease;
    }
    .provider-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }
    .status-badge {
        animation: pulse-badge 2s infinite;
    }
    @keyframes pulse-badge {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    .modal-enter {
        animation: modal-in 0.2s ease-out;
    }
    @keyframes modal-in {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endpush

@section('content')
<div x-data="aiProviderManager()" x-init="init()" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-microchip text-indigo-600 dark:text-indigo-400 mr-2"></i>
                AI Provider Management
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configure your own API keys for AI providers (BYOK). Smart routing selects the best provider per task.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showRoutingPanel = !showRoutingPanel"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-lg rounded-lg transition-colors">
                <i class="fas fa-route"></i>
                <span class="hidden sm:inline">Smart Routing</span>
            </button>
            <button @click="openAddModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline">Add Provider Key</span>
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-key text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Keys Configured</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="configuredCount + '/8'"></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-pie text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cache Hit Rate</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="cacheStats.hit_rate + '%'"></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Monthly Cost</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">$<span x-text="monthlyCost.toFixed(2)"></span></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-piggy-bank text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Est. Savings</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">$<span x-text="estimatedSavings.toFixed(2)"></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Savings Banner -->
    <div x-show="estimatedSavings > 0" x-cloak
         class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-leaf text-emerald-600 dark:text-emerald-400 text-xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">BYOK Cost Savings Active</h3>
            <p class="text-xs text-emerald-700 dark:text-emerald-300">
                Your configured keys saved an estimated $<span x-text="estimatedSavings.toFixed(2)"></span> this month
                by using your own API keys instead of platform credits. Cache hits reduced costs by $<span x-text="(cacheStats.saved_usd || 0).toFixed(2)"></span>.
            </p>
        </div>
        <i class="fas fa-check-circle text-emerald-500 text-2xl"></i>
    </div>

    <!-- Provider Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
        <template x-for="provider in providers" :key="provider.name">
            <div class="provider-card bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden"
                 :class="provider.byok_active ? 'ring-2 ring-indigo-500 ring-offset-2 dark:ring-offset-gray-900' : ''">
                <!-- Card Header -->
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                             :class="getProviderIconClass(provider.name)">
                            <i :class="getProviderIcon(provider.name)"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="provider.display_name"></h3>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="provider.byok_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 status-badge' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                  x-text="provider.byok_active ? '● Active' : '○ Inactive'">
                            </span>
                        </div>
                    </div>
                    <!-- Toggle Switch -->
                    <button @click="toggleProvider(provider)"
                            :disabled="!provider.byok_configured && !provider.byok_active"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                            :class="(provider.byok_active) ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'"
                            :title="provider.byok_configured ? 'Toggle active/inactive' : 'Configure key first'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="provider.byok_active ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>

                <!-- Card Body -->
                <div class="p-4 space-y-3">
                    <!-- API Key Status -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">API Key</span>
                        <span class="text-xs font-mono px-2 py-1 rounded"
                              :class="provider.byok_configured ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-700'"
                              x-text="provider.masked_key || 'Not configured'"></span>
                    </div>

                    <!-- Priority -->
                    <div x-show="provider.byok_configured" class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Priority</span>
                        <div class="flex items-center gap-1">
                            <template x-for="i in 5" :key="i">
                                <div class="w-2 h-2 rounded-full"
                                     :class="i <= (provider.priority || 0) ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
                            </template>
                            <span class="text-xs text-gray-400 ml-1" x-text="'(' + (provider.priority || 0) + '/5)'"></span>
                        </div>
                    </div>

                    <!-- Cost -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Cost / 1M tokens</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            $<span x-text="provider.cost_per_1m_input.toFixed(2)"></span> in / $<span x-text="provider.cost_per_1m_output.toFixed(2)"></span> out
                        </span>
                    </div>

                    <!-- Latency -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Avg. Latency</span>
                        <span class="text-xs text-gray-700 dark:text-gray-300" x-text="provider.avg_latency_ms + 'ms'"></span>
                    </div>

                    <!-- Supported Models -->
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Supported Models</span>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="(model, idx) in provider.models.slice(0, 3)" :key="idx">
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                      x-text="model"></span>
                            </template>
                            <span x-show="provider.models.length > 3"
                                  class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400"
                                  x-text="'+' + (provider.models.length - 3) + ' more'"></span>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <button @click="openEditModal(provider)"
                            class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            :class="provider.byok_configured ? '' : 'border-indigo-200 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400'">
                        <i class="fas" :class="provider.byok_configured ? 'fa-edit' : 'fa-plus'"></i>
                        <span x-text="provider.byok_configured ? 'Edit' : 'Add Key'"></span>
                    </button>
                    <button x-show="provider.byok_configured" @click="confirmDelete(provider)"
                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-red-600 dark:text-red-400 text-xs font-medium rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border border-transparent hover:border-red-200 dark:hover:border-red-800"
                            title="Delete key">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <button @click="testProvider(provider)"
                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-gray-500 dark:text-gray-400 text-xs font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="Test connection">
                        <i class="fas fa-vial"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Smart Routing Recommendations Panel -->
    <div x-show="showRoutingPanel" x-cloak x-transition
         class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-route text-indigo-600 dark:text-indigo-400 mr-2"></i>
                    Smart Routing Recommendations
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Best providers per task type based on cost, latency, and quality scores</p>
            </div>
            <button @click="showRoutingPanel = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(recs, taskType) in routingRecommendations" :key="taskType">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-7 h-7 rounded-md bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                <i class="fas fa-tasks text-indigo-600 dark:text-indigo-400 text-xs"></i>
                            </div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white capitalize" x-text="taskType"></h4>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(rec, idx) in recs" :key="idx">
                                <div class="flex items-center justify-between text-xs"
                                     :class="idx === 0 ? 'font-medium text-indigo-700 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'">
                                    <span class="flex items-center gap-1.5">
                                        <span x-show="idx === 0" class="text-yellow-500"><i class="fas fa-crown"></i></span>
                                        <span x-text="rec.provider"></span>
                                        <span x-show="rec.byok_available" class="text-green-500" title="BYOK available"><i class="fas fa-key text-[10px]"></i></span>
                                    </span>
                                    <span class="flex items-center gap-2">
                                        <span x-text="'$' + rec.cost_per_1m_input + '/1M'"></span>
                                        <span class="text-gray-400" x-text="rec.avg_latency_ms + 'ms'"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Cache Stats & Cost Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Cache Hit/Miss Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-database text-blue-600 dark:text-blue-400 mr-2"></i>
                    Cache Performance
                </h3>
            </div>
            <div class="p-6">
                <!-- Hit Rate Progress Bar -->
                <div class="mb-4">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-gray-500 dark:text-gray-400">Hit Rate</span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="cacheStats.hit_rate + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="bg-gradient-to-r from-indigo-500 to-blue-500 h-2.5 rounded-full transition-all duration-500"
                             :style="'width: ' + cacheStats.hit_rate + '%'"></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-lg font-bold text-green-700 dark:text-green-400" x-text="cacheStats.hits"></p>
                        <p class="text-xs text-green-600 dark:text-green-500">Hits</p>
                    </div>
                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p class="text-lg font-bold text-red-700 dark:text-red-400" x-text="cacheStats.misses"></p>
                        <p class="text-xs text-red-600 dark:text-red-500">Misses</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-300" x-text="cacheStats.total_requests"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center gap-3">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        Cached responses cost $0.00. Each hit saves ~$0.002 on average API call cost.
                    </p>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-coins text-purple-600 dark:text-purple-400 mr-2"></i>
                    Cost Breakdown
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Platform Credits Used</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">$<span x-text="(monthlyCost - byokSavings).toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">BYOK Provider Costs</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">$<span x-text="byokSavings.toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Cache Savings</span>
                        <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">-$<span x-text="(cacheStats.saved_usd || 0).toFixed(2)"></span></span>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Effective Cost</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">$<span x-text="monthlyCost.toFixed(2)"></span></span>
                    </div>
                </div>
                <!-- Budget Progress -->
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-gray-500 dark:text-gray-400">Monthly Budget</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            $<span x-text="monthlyCost.toFixed(2)"></span> / $<span x-text="budgetLimit.toFixed(2)"></span>
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500"
                             :class="(monthlyCost / budgetLimit * 100) > 80 ? 'bg-red-500' : (monthlyCost / budgetLimit * 100) > 60 ? 'bg-yellow-500' : 'bg-indigo-500'"
                             :style="'width: ' + Math.min((monthlyCost / budgetLimit * 100), 100) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optimization Recommendations -->
    <div x-show="optimizationRecommendations.length > 0" x-cloak
         class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                Optimization Recommendations
            </h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                <template x-for="rec in optimizationRecommendations" :key="rec.message">
                    <div class="flex items-start gap-3 p-3 rounded-lg"
                         :class="rec.priority === 'high' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : rec.priority === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800'">
                        <i class="fas mt-0.5"
                           :class="rec.priority === 'high' ? 'fa-exclamation-triangle text-red-500' : rec.priority === 'medium' ? 'fa-exclamation-circle text-yellow-500' : 'fa-info-circle text-blue-500'"></i>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="rec.message"></p>
                            <span class="inline-block mt-1 text-[10px] uppercase font-semibold tracking-wide"
                                  :class="rec.priority === 'high' ? 'text-red-600 dark:text-red-400' : rec.priority === 'medium' ? 'text-yellow-600 dark:text-yellow-400' : 'text-blue-600 dark:text-blue-400'"
                                  x-text="rec.priority + ' priority'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="closeModal()">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 modal-enter">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-key text-indigo-600 dark:text-indigo-400 mr-2"></i>
                    <span x-text="editingProvider ? 'Edit Provider Key' : 'Add Provider Key'"></span>
                </h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="saveProvider()" class="p-6 space-y-4">
                <!-- Provider Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                    <select x-model="formData.provider_name"
                            @change="updateProviderDefaults()"
                            :disabled="editingProvider"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Select a provider...</option>
                        <template x-for="p in providers" :key="p.name">
                            <option :value="p.name" x-text="p.display_name" :disabled="p.byok_configured && !editingProvider"></option>
                        </template>
                    </select>
                </div>

                <!-- API Key -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showApiKey ? 'text' : 'password'"
                               x-model="formData.api_key"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                               placeholder="sk-..." required>
                        <button type="button" @click="showApiKey = !showApiKey"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas" :class="showApiKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                        Encrypted at rest. Never exposed in logs or to other users.
                    </p>
                </div>

                <!-- Base URL (optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Base URL <span class="text-xs text-gray-400">(optional — for proxies/custom endpoints)</span>
                    </label>
                    <input type="url"
                           x-model="formData.api_base_url"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                           placeholder="https://api.openai.com/v1">
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Priority <span class="text-xs text-gray-400">(1=highest, 5=lowest)</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="range" x-model="formData.priority" min="1" max="5" step="1"
                               class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400 w-6 text-center" x-text="formData.priority"></span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mt-1 px-1">
                        <span>Primary</span>
                        <span>Fallback</span>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notes <span class="text-xs text-gray-400">(optional)</span>
                    </label>
                    <textarea x-model="formData.notes" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                              placeholder="e.g., Production key for social media team..."></textarea>
                </div>

                <!-- Form Error -->
                <div x-show="formError" x-cloak
                     class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-700 dark:text-red-400 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span x-text="formError"></span>
                    </p>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-save mr-1"></i>
                        <span x-text="editingProvider ? 'Update Key' : 'Save Key'"></span>
                    </button>
                    <button type="button" @click="closeModal()"
                            class="px-5 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="showDeleteModal = false">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 modal-enter">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Provider Key</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                    Are you sure you want to delete the API key for
                    <strong class="text-gray-900 dark:text-white" x-text="deletingProvider?.display_name"></strong>?
                    AI requests routed to this provider will fall back to other configured keys.
                </p>
                <div class="flex items-center gap-3">
                    <button @click="deleteProvider()"
                            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-trash-alt mr-1"></i>
                        Delete Key
                    </button>
                    <button @click="showDeleteModal = false"
                            class="px-5 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Result Toast -->
    <div x-show="testResult.show" x-cloak x-transition
         class="fixed bottom-6 right-6 z-50 max-w-sm">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border"
             :class="testResult.success ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800'">
            <i class="fas" :class="testResult.success ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'"></i>
            <p class="text-sm" :class="testResult.success ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" x-text="testResult.message"></p>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function aiProviderManager() {
    return {
        providers: [],
        showModal: false,
        showDeleteModal: false,
        showRoutingPanel: false,
        editingProvider: null,
        deletingProvider: null,
        showApiKey: false,
        formError: '',
        testResult: { show: false, success: false, message: '' },
        formData: {
            provider_name: '',
            api_key: '',
            api_base_url: '',
            priority: 3,
            notes: '',
        },
        cacheStats: { hits: 0, misses: 0, total_requests: 0, hit_rate: 0, saved_usd: 0 },
        routingRecommendations: {},
        optimizationRecommendations: [],
        monthlyCost: 0,
        budgetLimit: 200,
        byokSavings: 0,

        init() {
            this.loadData();
        },

        get configuredCount() {
            return this.providers.filter(p => p.byok_configured).length;
        },

        get estimatedSavings() {
            return parseFloat(this.cacheStats.saved_usd || 0) + this.byokSavings;
        },

        async loadData() {
            try {
                const response = await fetch('{{ route("ai-providers.index") }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Failed to load');
                const data = await response.json();
                this.providers = data.providers || [];
                this.cacheStats = data.cache_stats || this.cacheStats;
                this.routingRecommendations = data.routing || {};
                this.optimizationRecommendations = data.recommendations || [];
                this.monthlyCost = parseFloat(data.monthly_cost) || 0;
                this.budgetLimit = parseFloat(data.budget_limit) || 200;
                this.byokSavings = parseFloat(data.byok_savings) || 0;
            } catch (e) {
                console.error('Failed to load provider data:', e);
                // Fallback: show static providers
                this.providers = this.getStaticProviders();
            }
        },

        getStaticProviders() {
            return [
                { name: 'openai', display_name: 'OpenAI', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 2.50, cost_per_1m_output: 10.00, avg_latency_ms: 1200, models: ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo', 'o1-preview', 'o1-mini'] },
                { name: 'anthropic', display_name: 'Anthropic Claude', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 3.00, cost_per_1m_output: 15.00, avg_latency_ms: 1500, models: ['claude-3-5-sonnet-20241022', 'claude-3-haiku-20240307', 'claude-3-opus-20240229'] },
                { name: 'google', display_name: 'Google Gemini', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 1.25, cost_per_1m_output: 5.00, avg_latency_ms: 1100, models: ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-1.0-pro'] },
                { name: 'mistral', display_name: 'Mistral AI', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 2.00, cost_per_1m_output: 6.00, avg_latency_ms: 1300, models: ['mistral-large', 'mistral-small', 'mistral-tiny'] },
                { name: 'groq', display_name: 'Groq', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 0.10, cost_per_1m_output: 0.10, avg_latency_ms: 400, models: ['llama-3.1-70b', 'llama-3.1-8b', 'qwen-3.8-27b'] },
                { name: 'nvidia_nim', display_name: 'NVIDIA NIM', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 0.40, cost_per_1m_output: 1.00, avg_latency_ms: 700, models: ['meta/llama-3.1-70b', 'meta/llama-3.1-8b'] },
                { name: 'nous_portal', display_name: 'Nous Portal', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 0.20, cost_per_1m_output: 0.60, avg_latency_ms: 800, models: ['hermes-3-llama-3.1-70b', 'hermes-3-llama-3.1-8b'] },
                { name: 'ollama', display_name: 'Ollama (Local)', byok_configured: false, byok_active: false, priority: null, masked_key: null, cost_per_1m_input: 0.00, cost_per_1m_output: 0.00, avg_latency_ms: 200, models: ['llama3.1:8b', 'llama3.1:70b', 'llama3.2:3b', 'mistral:7b', 'qwen2.5:7b'] },
            ];
        },

        openAddModal() {
            this.editingProvider = null;
            this.formData = { provider_name: '', api_key: '', api_base_url: '', priority: 3, notes: '' };
            this.showApiKey = false;
            this.formError = '';
            this.showModal = true;
        },

        openEditModal(provider) {
            this.editingProvider = provider;
            this.formData = {
                provider_name: provider.name,
                api_key: '',
                api_base_url: '',
                priority: provider.priority || 3,
                notes: '',
            };
            this.showApiKey = false;
            this.formError = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingProvider = null;
            this.formError = '';
        },

        updateProviderDefaults() {
            // Could set default base URLs per provider
        },

        async saveProvider() {
            if (!this.formData.provider_name) {
                this.formError = 'Please select a provider.';
                return;
            }
            if (!this.formData.api_key && !this.editingProvider) {
                this.formError = 'API key is required.';
                return;
            }

            this.formError = '';
            try {
                const url = this.editingProvider
                    ? `/ai-providers/${this.formData.provider_name}`
                    : '/ai-providers';
                const method = this.editingProvider ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.formData),
                });

                const data = await response.json();
                if (!response.ok) {
                    this.formError = data.message || 'Failed to save provider key.';
                    return;
                }

                this.closeModal();
                window.showToast(data.message || 'Provider key saved successfully!', 'success');
                this.loadData();
            } catch (e) {
                this.formError = 'Network error. Please try again.';
            }
        },

        confirmDelete(provider) {
            this.deletingProvider = provider;
            this.showDeleteModal = true;
        },

        async deleteProvider() {
            if (!this.deletingProvider) return;
            try {
                const response = await fetch(`/ai-providers/${this.deletingProvider.name}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (!response.ok) {
                    window.showToast(data.message || 'Failed to delete key.', 'error');
                    return;
                }
                this.showDeleteModal = false;
                window.showToast(`${this.deletingProvider.display_name} key deleted.`, 'success');
                this.deletingProvider = null;
                this.loadData();
            } catch (e) {
                window.showToast('Network error.', 'error');
            }
        },

        async toggleProvider(provider) {
            if (!provider.byok_configured && !provider.byok_active) return;
            try {
                const response = await fetch(`/ai-providers/${provider.name}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (!response.ok) {
                    window.showToast(data.message || 'Failed to toggle.', 'error');
                    return;
                }
                window.showToast(`${provider.display_name} is now ${data.is_active ? 'active' : 'inactive'}.`, 'success');
                this.loadData();
            } catch (e) {
                window.showToast('Network error.', 'error');
            }
        },

        async testProvider(provider) {
            try {
                const response = await fetch(`/ai-providers/${provider.name}/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                this.testResult = {
                    show: true,
                    success: data.success,
                    message: data.success
                        ? `${provider.display_name}: Connected successfully! (${data.latency_ms}ms)`
                        : `${provider.display_name}: ${data.message || 'Connection failed'}`,
                };
                setTimeout(() => { this.testResult.show = false; }, 5000);
                this.loadData();
            } catch (e) {
                this.testResult = { show: true, success: false, message: 'Network error during test.' };
                setTimeout(() => { this.testResult.show = false; }, 5000);
            }
        },

        getProviderIcon(name) {
            const icons = {
                openai: 'fas fa-robot',
                anthropic: 'fas fa-brain',
                google: 'fab fa-google',
                mistral: 'fas fa-wind',
                groq: 'fas fa-bolt',
                nvidia_nim: 'fas fa-microchip',
                nous_portal: 'fas fa-portal',
                ollama: 'fas fa-server',
            };
            return icons[name] || 'fas fa-cog';
        },

        getProviderIconClass(name) {
            const classes = {
                openai: 'bg-emerald-100 dark:bg-emerald-900/30',
                anthropic: 'bg-orange-100 dark:bg-orange-900/30',
                google: 'bg-blue-100 dark:bg-blue-900/30',
                mistral: 'bg-purple-100 dark:bg-purple-900/30',
                groq: 'bg-yellow-100 dark:bg-yellow-900/30',
                nvidia_nim: 'bg-green-100 dark:bg-green-900/30',
                nous_portal: 'bg-indigo-100 dark:bg-indigo-900/30',
                ollama: 'bg-gray-100 dark:bg-gray-700',
            };
            return classes[name] || 'bg-gray-100 dark:bg-gray-700';
        },
    };
}
</script>
@endpush
@endsection
