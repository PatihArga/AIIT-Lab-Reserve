@props(['align' => 'right', 'width' => 48])

@php
    $alignment = match($align) {
        'left'   => 'origin-top-left start-0',
        'right'  => 'origin-top-right end-0',
        default  => 'origin-top',
    };
    $widthClass = match($width) {
        48 => 'w-48',
        56 => 'w-56',
        64 => 'w-64',
        default => 'w-' . $width,
    };
@endphp

<div class="relative inline-flex"
     x-data="{ open: false }"
     @click.outside="open = false"
     @close.stop="open = false">

    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-40 mt-2 {{ $widthClass }} {{ $alignment }} rounded-md bg-white shadow-modal border border-rule"
         x-cloak>
        <div class="py-1.5">
            {{ $slot }}
        </div>
    </div>
</div>
