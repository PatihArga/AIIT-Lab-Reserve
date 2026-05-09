@props([
    'name',
    'value',
    'label',
    'description' => null,
    'checked' => false,
])

<label class="relative flex gap-3 p-4 rounded-md border border-rule-strong cursor-pointer
              hover:border-ink-500 transition-all
              has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 has-[:checked]:shadow-subtle">
    <input type="radio"
           name="{{ $name }}"
           value="{{ $value }}"
           {{ $checked ? 'checked' : '' }}
           {{ $attributes->merge(['class' => 'mt-1 text-ink-700 focus:ring-ink-500 border-rule-strong']) }}>
    <div class="flex-1">
        <div class="text-sm font-semibold text-ink-900">{{ $label }}</div>
        @if ($description)
            <div class="text-xs text-ink-700/60 mt-0.5">{{ $description }}</div>
        @endif
    </div>

    @if (isset($extra))
        {{ $extra }}
    @endif
</label>
