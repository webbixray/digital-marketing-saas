@extends('layouts.unified')
@section('title', 'Create Report')
@section('content')
<x-flash-messages />
<div class="max-w-2xl mx-auto">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('reports.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Reports</a>
        <span>/</span>
        <span class="text-gray-900 dark:text-white">Create</span>
    </nav>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Create Report</h3>
        </div>
        <form action="{{ route('reports.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label for="name" class="form-label">Report Name</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required placeholder="e.g., Q4 Social Media Performance">
                </div>
                <div>
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-input" required>
                        <option value="">Select type...</option>
                        <option value="engagement" {{ old('type') === 'engagement' ? 'selected' : '' }}>Engagement</option>
                        <option value="performance" {{ old('type') === 'performance' ? 'selected' : '' }}>Performance</option>
                        <option value="conversion" {{ old('type') === 'conversion' ? 'selected' : '' }}>Conversion</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-input" value="{{ old('start_date') }}" required>
                    </div>
                    <div>
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-input" value="{{ old('end_date') }}" required>
                    </div>
                </div>
                <div>
                    <label for="platform" class="form-label">Platforms <span class="text-gray-400 dark:text-gray-500 font-normal">(hold Ctrl/Cmd to select multiple)</span></label>
                    <select name="platform[]" id="platform" class="form-input" multiple size="5">
                        <option value="facebook" {{ in_array('facebook', old('platform', [])) ? 'selected' : '' }}>Facebook</option>
                        <option value="instagram" {{ in_array('instagram', old('platform', [])) ? 'selected' : '' }}>Instagram</option>
                        <option value="twitter" {{ in_array('twitter', old('platform', [])) ? 'selected' : '' }}>Twitter / X</option>
                        <option value="linkedin" {{ in_array('linkedin', old('platform', [])) ? 'selected' : '' }}>LinkedIn</option>
                        <option value="tiktok" {{ in_array('tiktok', old('platform', [])) ? 'selected' : '' }}>TikTok</option>
                        <option value="youtube" {{ in_array('youtube', old('platform', [])) ? 'selected' : '' }}>YouTube</option>
                        <option value="google" {{ in_array('google', old('platform', [])) ? 'selected' : '' }}>Google</option>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="submit" class="btn-primary">Generate Report</button>
                <a href="{{ route('reports.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
