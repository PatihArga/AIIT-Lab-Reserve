@props([
    'label' => null,
    'title' => null,
    'count' => null,
])

<section {{ $attributes->merge(['class' => 'section']) }}>
    @if ($label || $title || $count !== null)
        <div class="flex items-baseline justify-between mb-4">
            <div>
                @if ($label)
                    <div class="section-label flex items-baseline gap-2">
                        <span>{{ $label }}</span>
                        @if ($count !== null)
                            <span class="font-mono text-ink-700/40 normal-case tracking-normal">· {{ $count }}</span>
                        @endif
                    </div>
                @endif
                @if ($title)
                    <h2 class="section-title">{{ $title }}</h2>
                @endif
            </div>

            @if (isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
