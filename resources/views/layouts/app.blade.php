@php $isAdmin = Auth::user()?->role === 'admin'; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body x-data="{ sidebarOpen: window.innerWidth >= 1024, mobileOpen: false }" class="font-sans antialiased min-h-screen bg-bg">

    @if ($isAdmin)
        {{-- ===== ADMIN SHELL — left sidebar ===== --}}

        {{-- Mobile tap-to-close overlay (behind sidebar, above page content) --}}
        <div x-show="mobileOpen"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileOpen = false"
             class="lg:hidden fixed inset-0 z-[35] bg-ink-900/60 backdrop-blur-sm"
             style="display:none"></div>

        {{-- Sidebar (uses mobileOpen on mobile, sidebarOpen on desktop) --}}
        <div class="print:hidden">
            <x-app-sidebar />
        </div>

        {{-- Mobile sidebar open trigger — floating icon button, hidden when drawer is open --}}
        <button x-show="!mobileOpen"
                @click="mobileOpen = true"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="lg:hidden print:hidden fixed top-4 left-4 z-50 w-9 h-9 flex items-center justify-center rounded-xl bg-ink-900 text-white shadow-lg"
                style="display:none"
                aria-label="Buka sidebar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        {{-- Desktop sidebar collapse/expand toggle pill --}}
        <button @click="sidebarOpen = !sidebarOpen"
                :style="{ left: sidebarOpen ? '272px' : '16px' }"
                class="hidden lg:flex print:!hidden fixed top-[22px] z-50 w-7 h-7 rounded-full bg-white border border-rule shadow-card items-center justify-center text-ink-700/40 hover:text-ink-900 hover:bg-ink-50 transition-all duration-300"
                title="Toggle sidebar">
            <svg :class="{ 'rotate-180': !sidebarOpen }" class="w-3.5 h-3.5 transition-transform duration-200 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
    @else
        {{-- ===== USER SHELL — top navigation ===== --}}
        <x-top-nav />
    @endif

    {{-- Main content wrapper — shifts right on desktop only when the admin sidebar is open --}}
    <div class="min-h-screen flex flex-col transition-all duration-300 print:!ml-0 print:!pt-0 {{ $isAdmin ? 'pt-14 lg:pt-0' : '' }}"
         @if ($isAdmin) :class="sidebarOpen ? 'lg:ml-shell' : 'lg:ml-0'" @endif>

        {{-- Optional sticky top-bar slot --}}
        @isset($topbar)
            <header class="sticky top-0 z-20 bg-bg/85 backdrop-blur-sm border-b border-rule">
                <div class="px-4 sm:px-6 lg:px-8 py-3 flex items-center gap-4">
                    {{ $topbar }}
                </div>
            </header>
        @endisset

        {{-- Page heading slot --}}
        @isset($header)
            <header class="px-4 sm:px-6 lg:px-8 pt-6 lg:pt-8 pb-0">
                <div class="max-w-content mx-auto">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Main content --}}
        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-5 lg:py-8">
            <div class="max-w-content mx-auto">
                {{ $slot }}
            </div>
        </main>

        {{-- Footer --}}
        <footer class="px-4 sm:px-6 lg:px-8 py-5 lg:py-6 border-t border-rule print:hidden">
            <div class="max-w-content mx-auto flex items-center justify-between text-[0.7rem] uppercase tracking-label text-ink-700/40 font-semibold">
                <span>UKRIDA · Lab Reserve</span>
                <span>v1.0.0</span>
            </div>
        </footer>

    </div>

    {{-- Toast container --}}
    <div id="toast-root" class="fixed bottom-6 right-4 lg:right-6 z-50 flex flex-col gap-2 max-w-sm print:hidden">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="bg-white border border-status-approved/30 shadow-card rounded-xl px-4 py-3 flex items-start gap-3"
                 style="display:none">
                <svg class="w-5 h-5 text-status-approved shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div class="flex-1 text-sm text-ink-900">{{ session('success') }}</div>
                <button @click="show = false" class="text-ink-700/40 hover:text-ink-900" aria-label="Tutup">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show"
                 x-init="setTimeout(() => show = false, 6000)"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="bg-white border border-status-rejected/30 shadow-card rounded-xl px-4 py-3 flex items-start gap-3"
                 style="display:none">
                <svg class="w-5 h-5 text-status-rejected shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div class="flex-1 text-sm text-ink-900">{{ session('error') }}</div>
                <button @click="show = false" class="text-ink-700/40 hover:text-ink-900" aria-label="Tutup">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        @endif
    </div>

    @stack('scripts')
</body>
</html>
