@php
    $user = Auth::user();
    $initials = $user ? strtoupper(substr($user->name, 0, 1) . (str_contains($user->name, ' ') ? substr($user->name, strpos($user->name, ' ') + 1, 1) : '')) : 'U';
    $roleLabel = match($user?->role) {
        'admin'    => 'Admin',
        'team'     => 'Tim',
        'lecturer' => 'Dosen',
        default    => 'Pengguna',
    };
@endphp

<div class="border-t border-white/5 px-3 py-3"
     x-data="{ open: false }"
     @click.outside="open = false">

    <button type="button"
            @click="open = !open"
            class="w-full flex items-center gap-3 px-2 py-2 rounded-md hover:bg-white/5 transition-colors text-left">
        <div class="w-9 h-9 rounded-full bg-mark-500 text-ink-900 flex items-center justify-center font-bold text-sm">
            {{ $initials }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-white truncate">{{ $user?->name }}</div>
            <div class="text-[0.65rem] uppercase tracking-label text-white/40 font-semibold">{{ $roleLabel }}</div>
        </div>
        <svg class="w-4 h-4 text-white/40 transition-transform"
             :class="{ 'rotate-180': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak
         class="mt-2 space-y-1 px-1 pb-1">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 text-sm text-white/70 hover:text-white hover:bg-white/5 rounded-md">
                Keluar
            </button>
        </form>
    </div>
</div>
