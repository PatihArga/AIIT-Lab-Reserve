@php
    $user = Auth::user();
    $current = request()->route()?->getName() ?? '';
    $initials = $user
        ? strtoupper(substr($user->name, 0, 1) . (str_contains($user->name, ' ') ? substr($user->name, strpos($user->name, ' ') + 1, 1) : ''))
        : 'U';
    $roleLabel = match ($user?->role) {
        'admin'    => 'Admin',
        'team'     => 'Tim',
        'lecturer' => 'Dosen',
        default    => 'Pengguna',
    };

    // Icons (stroke=currentColor → inherit the link colour).
    $icoDashboard = '<svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
    $icoCalendar  = '<svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>';
    $icoHistory   = '<svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';

    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard',       'icon' => $icoDashboard, 'active' => $current === 'dashboard'],
        ['label' => 'Kalender',  'route' => 'calendar.index',  'icon' => $icoCalendar,  'active' => $current === 'calendar.index'],
        ['label' => 'Riwayat',   'route' => 'booking.history', 'icon' => $icoHistory,   'active' => in_array($current, ['booking.history', 'booking.show'], true)],
    ];

    $linkBase = 'inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors whitespace-nowrap';
    $linkOn   = 'bg-white/10 text-white';
    $linkOff  = 'text-white/55 hover:text-white hover:bg-white/5';
@endphp

<header class="sticky top-0 z-40 bg-ink-900 text-white shadow-sm">
    <div class="max-w-content mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 sm:gap-6 h-16">

            {{-- Brand --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
                <div class="w-9 h-9 rounded-md bg-mark-500 text-ink-900 flex items-center justify-center font-bold text-sm">LR</div>
                <div class="leading-tight hidden sm:block">
                    <div class="text-[0.6rem] uppercase tracking-label text-mark-500/90 font-semibold leading-none">UKRIDA</div>
                    <div class="text-sm font-semibold tracking-tight mt-0.5">Lab Reserve</div>
                </div>
            </a>

            {{-- Nav — always horizontal --}}
            <nav class="flex items-center gap-1 overflow-x-auto">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="{{ $linkBase }} {{ $link['active'] ? $linkOn : $linkOff }}">
                        {!! $link['icon'] !!}
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="flex-1"></div>

            {{-- User menu --}}
            <div class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open"
                        class="flex items-center gap-2.5 pl-1.5 pr-2 py-1.5 rounded-md hover:bg-white/10 transition-colors">
                    <div class="w-9 h-9 rounded-full bg-mark-500 text-ink-900 flex items-center justify-center font-bold text-xs shrink-0">{{ $initials }}</div>
                    <div class="text-left leading-tight hidden lg:block">
                        <div class="text-sm font-semibold text-white truncate max-w-[150px] leading-none">{{ $user?->name }}</div>
                        <div class="text-[0.6rem] uppercase tracking-label text-white/45 font-semibold mt-1">{{ $roleLabel }}</div>
                    </div>
                    <svg class="w-4 h-4 text-white/40 transition-transform shrink-0" :class="{ 'rotate-180': open }"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-card border border-rule py-1 z-50">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
