@props([
    'title' => 'Belum ada data',
    'desc' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    @if (isset($icon))
        <div class="text-ink-700/30 mb-2">{{ $icon }}</div>
    @else
        <div class="w-12 h-12 rounded-full bg-ink-50 flex items-center justify-center text-ink-700/40">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
    @endif

    <p class="empty-state-title">{{ $title }}</p>
    @if ($desc)
        <p class="empty-state-desc">{{ $desc }}</p>
    @endif

    @if (isset($action))
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
