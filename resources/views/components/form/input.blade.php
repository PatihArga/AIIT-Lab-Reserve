@props(['type' => 'text', 'name'])

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->merge(['class' => 'form-input']) }}
/>
