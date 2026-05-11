@props([
    'eyebrow' => null,
    'title',
    'meta' => null,
])

<div class="page-header flex-col sm:flex-row items-start sm:items-end gap-4 sm:gap-6">
    <div class="flex-1 min-w-0">
        @if ($eyebrow)
            <div class="page-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="page-title text-2xl sm:text-3xl truncate">{{ $title }}</h1>
        @if ($meta)
            <div class="page-meta line-clamp-2">{{ $meta }}</div>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            {{ $actions }}
        </div>
    @endif
</div>
