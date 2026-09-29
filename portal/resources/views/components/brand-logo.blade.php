@props([
    'class' => '',
    'alt' => 'مدد',
    'eager' => false,
])
@php
    $logo = public_path('brand/madd-logo.png');
    $fallbackSvg = public_path('brand/olivia-brand-full.svg');
@endphp
@if(file_exists($logo))
    <img
        src="{{ asset('brand/madd-logo.png') }}?v={{ filemtime($logo) }}"
        alt="{{ $alt }}"
        class="{{ $class }}"
        width="260"
        height="110"
        @if($eager) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif
    >
@elseif(file_exists($fallbackSvg))
    <img
        src="{{ asset('brand/olivia-brand-full.svg') }}?v={{ filemtime($fallbackSvg) }}"
        alt="{{ $alt }}"
        class="{{ $class }}"
        loading="lazy"
        decoding="async"
    >
@else
    <span class="{{ $class }}">{{ $alt }}</span>
@endif
