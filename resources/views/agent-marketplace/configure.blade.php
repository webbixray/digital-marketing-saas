@extends('layouts.unified')
@section('title', 'Configure Agent')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="hover:text-gray-700"><a href="{{ route('agent-marketplace.index') }}">Marketplace</a></li>
 <li class="text-gray-900 font-medium">Configure</li>
@endsection

@section('content')
<x-flash-messages />
<div class="max-w-2xl mx-auto space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Configure Agent</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Customize agent settings for your agency.</p>
    </div>

    <form method="POST" action="{{ route('agent-marketplace.configure') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6 space-y-6">
        @csrf
        <input type="hidden" name="item_id" value="{{ $item->id ?? '' }}">

        <!-- Basic Settings -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-sliders mr-2"></i>Basic Settings</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agent Name (display)</label>
                    <input type="text" name="config[display_name]" value="{{ $item->name ?? '' }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="config[is_active]" value="1" checked class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        Agent is active
                    </label>
                </div>
            </div>
        </div>

        <!-- API / Integration Settings -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-plug mr-2"></i>API & Integration</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key (if required)</label>
                    <input type="password" name="config[api_key]" placeholder="••••••••" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</label>
                    <input type="url" name="config[webhook_url]" placeholder="https://your-domain.com/webhook" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>

        <!-- Feature Flags -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-toggle-on mr-2"></i>Feature Options</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="config[auto_execute]" value="1" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    Auto-execute on schedule
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="config[send_notifications]" value="1" checked class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    Send notifications on completion
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="config[log_results]" value="1" checked class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    Log execution results
                </label>
            </div>
        </div>

        <!-- Schedule -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-clock mr-2"></i>Schedule</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Execution Frequency</label>
                <select name="config[frequency]" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="manual">Manual only</option>
                    <option value="hourly">Every hour</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('agent-marketplace.index') }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Marketplace
            </a>
            <div class="flex gap-3">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                    <i class="fas fa-save mr-1"></i> Save Configuration
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
