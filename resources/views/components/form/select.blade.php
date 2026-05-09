@props([
    'name',
    'options' => [],     // ['key' => 'Label']
    'placeholder' => null,
    'selected' => null,
])

<select
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->merge(['class' => 'form-select']) }}
>
    @if ($placeholder)
        <option value="" disabled {{ $selected ? '' : 'selected' }}>{{ $placeholder }}</option>
    @endif

    @if (! empty($options))
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected(old($name, $selected) == $value)>{{ $label }}</option>
        @endforeach
    @else
        {{ $slot }}
    @endif
</select>
