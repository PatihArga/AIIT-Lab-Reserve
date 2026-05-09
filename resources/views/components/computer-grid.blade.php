@props([
    'computers' => [],     // collection or array of computers
    'selectable' => false, // if true, render as checkboxes
    'selected' => [],      // array of selected computer IDs
    'name' => 'computers', // form field name
])

<div {{ $attributes->merge(['class' => 'grid grid-cols-3 gap-3']) }}>
    @foreach ($computers as $computer)
        @php
            $disabled = ($computer->status ?? 'online') !== 'online';
            $isSelected = in_array($computer->id, (array) $selected);
            $statusLabel = match($computer->status ?? 'online') {
                'online'      => 'Tersedia',
                'maintenance' => 'Pemeliharaan',
                'offline'     => 'Nonaktif',
                default       => 'Tersedia',
            };
        @endphp

        @if ($selectable && ! $disabled)
            <label class="relative flex flex-col items-center justify-center gap-1 aspect-square rounded-md
                          border border-rule-strong cursor-pointer transition-all
                          hover:border-ink-500
                          has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/60 has-[:checked]:shadow-subtle">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $computer->id }}"
                       {{ $isSelected ? 'checked' : '' }}
                       class="sr-only peer">

                <span class="absolute top-2 right-2 w-4 h-4 rounded border border-rule-strong
                             peer-checked:bg-mark-500 peer-checked:border-mark-500
                             flex items-center justify-center transition-all">
                    <svg class="w-3 h-3 text-ink-900 opacity-0 peer-checked:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>

                <svg class="w-7 h-7 text-ink-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="font-mono text-sm font-semibold text-ink-900">{{ $computer->label }}</span>
                <span class="text-[0.6rem] uppercase tracking-label font-semibold text-ink-700/50">{{ $statusLabel }}</span>
            </label>
        @else
            <div class="relative flex flex-col items-center justify-center gap-1 aspect-square rounded-md
                        border border-rule-strong
                        {{ $disabled ? 'bg-ink-50/40 opacity-60' : '' }}">
                @if ($disabled)
                    <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-status-cancelled"></span>
                @else
                    <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-status-approved"></span>
                @endif

                <svg class="w-7 h-7 text-ink-700/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="font-mono text-sm font-semibold text-ink-900">{{ $computer->label }}</span>
                <span class="text-[0.6rem] uppercase tracking-label font-semibold text-ink-700/50">{{ $statusLabel }}</span>
            </div>
        @endif
    @endforeach
</div>
