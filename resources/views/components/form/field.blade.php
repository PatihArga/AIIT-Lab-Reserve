@props([
    'name'     => null,
    'label'    => null,
    'hint'     => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'form-field']) }}>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif
               class="form-label {{ $required ? 'form-required' : '' }}">
            {{ $label }}
        </label>
    @endif

    {{ $slot }}

    @if ($hint && $name && ! $errors->has($name))
        <p class="form-hint">{{ $hint }}</p>
    @endif

    @if ($name && $errors->has($name))
        <p class="form-error">{{ $errors->first($name) }}</p>
    @endif
</div>
