@props(['name', 'rows' => 4])

<textarea
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'form-textarea']) }}
>{{ $slot }}</textarea>
