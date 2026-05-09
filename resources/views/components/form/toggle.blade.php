@props(['name', 'checked' => false, 'label' => null])

<label class="inline-flex items-center gap-3 cursor-pointer select-none">
    <span class="relative inline-flex">
        <input type="checkbox"
               name="{{ $name }}"
               value="1"
               {{ $checked ? 'checked' : '' }}
               {{ $attributes->merge(['class' => 'sr-only peer']) }}>
        <span class="w-10 h-6 rounded-full bg-rule-strong peer-checked:bg-ink-700 transition-colors"></span>
        <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow-subtle
                     peer-checked:translate-x-4 transition-transform"></span>
    </span>
    @if ($label)
        <span class="text-sm text-ink-900">{{ $label }}</span>
    @endif
</label>
