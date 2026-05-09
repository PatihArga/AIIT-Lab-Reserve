@props([
    'eyebrow' => null,
    'title',
    'meta' => null,
])

<div class="page-header">
    <div>
        @if ($eyebrow)
            <div class="page-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if ($meta)
            <div class="page-meta">{{ $meta }}</div>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
