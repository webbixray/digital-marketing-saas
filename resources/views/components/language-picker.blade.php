@props(['active' => false])

@php
$classes = $active
    ? 'bg-blue-600 text-white px-3 py-1 rounded-md text-sm'
    : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-1 rounded-md text-sm';
@endphp

@if(app()->getLocale() !== 'en')
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        {{ strtoupper(app()->getLocale()) }}
    </button>
@else
    <span class="{{ $classes }}">{{ strtoupper(app()->getLocale()) }}</span>
@endif
