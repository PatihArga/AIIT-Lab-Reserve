@props([
    'value',
    'label',
    'meta' => null,
    'mark' => false,
])

<div {{ $attributes->merge(['class' => 'stat-hero']) }}>
    <div class="{{ $mark ? 'stat-hero-value-mark' : 'stat-hero-value' }}">{{ $value }}</div>
    <div class="stat-hero-label">{{ $label }}</div>
    @if ($meta)
        <div class="stat-hero-meta">{{ $meta }}</div>
    @endif
</div>
