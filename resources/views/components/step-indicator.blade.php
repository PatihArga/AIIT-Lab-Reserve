@props([
    'steps' => [],     // ['Pilih Tipe', 'Logbook', 'Jadwal', 'Tinjau', 'Kirim']
    'current' => 1,    // 1-indexed current step
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    @foreach ($steps as $i => $label)
        @php
            $stepNum = $i + 1;
            $state = $stepNum < $current ? 'complete' : ($stepNum === $current ? 'active' : 'pending');
            $dotClass = match($state) {
                'complete' => 'step-dot-complete',
                'active'   => 'step-dot-active',
                'pending'  => 'step-dot-pending',
            };
            $labelClass = match($state) {
                'active'  => 'text-ink-900 font-semibold',
                'complete'=> 'text-ink-700/50',
                'pending' => 'text-ink-700/40',
            };
        @endphp

        <div class="flex items-center gap-2.5">
            <div class="{{ $dotClass }}">
                @if ($state === 'complete')
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    {{ $stepNum }}
                @endif
            </div>
            <span class="text-[0.7rem] uppercase tracking-label {{ $labelClass }} hidden sm:inline">
                {{ $label }}
            </span>
        </div>

        @if (! $loop->last)
            <div class="{{ $stepNum < $current ? 'step-connector-complete' : 'step-connector' }}"></div>
        @endif
    @endforeach
</div>
