@props([
    'role' => Auth::user()?->role ?? 'lecturer',
])

@php
    $isAdmin = $role === 'admin';
    $current = request()->route()?->getName() ?? '';
@endphp

{{--
    On mobile  : the sidebar is a full-height drawer that slides in from the left
                 (translate-x controlled by `mobileOpen`). The width is always 256px.
    On desktop : the sidebar collapses to width-0 / full via `sidebarOpen`.
--}}
<aside class="fixed inset-y-0 left-0 bg-ink-900 text-white flex flex-col z-40 transition-all duration-300 overflow-hidden
              -translate-x-full lg:translate-x-0
              w-[256px]"
       :class="{
           '-translate-x-full': !mobileOpen,
           'translate-x-0 shadow-2xl': mobileOpen,
           'lg:max-w-[256px]': sidebarOpen,
           'lg:max-w-0': !sidebarOpen,
           'lg:!translate-x-0': true
       }">
<div style="width:256px;min-width:256px;" class="flex flex-col h-full">

    {{-- Mobile close button --}}
    <button @click="mobileOpen = false" class="lg:hidden absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-white/40 hover:bg-white/10 hover:text-white transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>

    {{-- Logo / brand --}}
    <div class="px-6 pt-7 pb-6 flex items-center gap-3 border-b border-white/5">
        <img src="{{ asset('images/ukrida_logo.png') }}" alt="UKRIDA" class="h-10 w-auto shrink-0">
        <div>
            <div class="text-[0.6rem] uppercase tracking-label text-mark-500/90 font-semibold leading-none">AIIT</div>
            <div class="text-sm font-semibold tracking-tight mt-1">Lab Reserve</div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
        @if ($isAdmin)
            <div class="nav-section-label">Beranda</div>
            <a href="{{ route('admin.dashboard') }}"
               class="nav-item {{ str_starts_with($current, 'admin.dashboard') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section-label">Operasional</div>
            <a href="{{ route('admin.requests.index') }}"
               class="nav-item {{ str_starts_with($current, 'admin.requests') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Permintaan</span>
                @php $pendingCount = \App\Models\Booking::whereIn('status', ['submitted', 'under_review'])->count(); @endphp
                @if ($pendingCount > 0)
                    <span class="ml-auto font-mono text-xs px-1.5 py-0.5 rounded bg-mark-500 text-ink-900 font-semibold">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.computers.index') }}"
               class="nav-item {{ str_starts_with($current, 'admin.computers') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>Komputer</span>
            </a>

            <div class="nav-section-label">Manajemen</div>
            <a href="{{ route('admin.users.index') }}"
               class="nav-item {{ str_starts_with($current, 'admin.users') || str_starts_with($current, 'admin.teams') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Pengguna & Tim</span>
            </a>
            <a href="{{ route('admin.reports.index') }}"
               class="nav-item {{ str_starts_with($current, 'admin.reports') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Laporan</span>
            </a>

            <div class="nav-section-label">Sistem</div>
            <a href="{{ route('admin.audit-log.index') }}"
               class="nav-item {{ str_starts_with($current, 'admin.audit-log') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Audit Log</span>
            </a>
            <a href="{{ route('admin.settings.index') }}"
               class="nav-item {{ str_starts_with($current, 'admin.settings') ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Pengaturan</span>
            </a>
        @else
            <div class="nav-section-label">Beranda</div>
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ $current === 'dashboard' ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section-label">Reservasi</div>
            <a href="{{ route('calendar.index') }}"
               class="nav-item {{ $current === 'calendar.index' ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                <span>Kalender</span>
            </a>
            <a href="{{ route('booking.history') }}"
               class="nav-item {{ $current === 'booking.history' || $current === 'booking.show' ? 'active' : '' }}">
                <svg class="nav-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Riwayat</span>
            </a>
        @endif
    </nav>

    {{-- User menu --}}
    <x-user-menu />

</div>
</aside>
