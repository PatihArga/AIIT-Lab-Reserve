@props([
    'href' => '#',
    'tag' => 'a',
    'danger' => false,
])

@php
    $base = 'flex w-full items-center gap-2 px-3 py-1.5 text-sm transition-colors';
    $tone = $danger
        ? 'text-status-rejected hover:bg-status-rejected/5'
        : 'text-ink-900 hover:bg-ink-50';
@endphp

@if ($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $tone"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $tag === 'button' ? 'button' : 'submit' }}"
            {{ $attributes->merge(['class' => "$base $tone text-left"]) }}>
        {{ $slot }}
    </button>
@endif
