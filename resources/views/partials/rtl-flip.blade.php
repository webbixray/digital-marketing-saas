@props(['icon', 'class' => ''])

@php
    $isRTL = app(\App\View\Composers\LanguageComposer::class)->isRTLLocale();
    $shouldFlip = $isRTL && in_array($icon, [
        'arrow-left', 'arrow-right',
        'chevron-left', 'chevron-right',
        'caret-left', 'caret-right',
        'angle-left', 'angle-right',
        'angle-double-left', 'angle-double-right',
        'sign-out-alt', 'reply', 'share', 'paper-plane',
        'external-link-alt',
    ]);
@endphp

<i {{ $attributes->merge(['class' => 'fas fa-' . $icon . ($shouldFlip ? ' rtl-flip' : '') . ($class ? ' ' . $class : '')]) }} aria-hidden="true"></i>
