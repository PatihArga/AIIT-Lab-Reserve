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
<body x-data="{ sidebarOpen: window.innerWidth >= 1024 }" class="font-sans antialiased min-h-screen bg-bg">

    {{-- Mobile overlay backdrop --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-ink-900/50 backdrop-blur-sm z-20 lg:hidden"
         x-cloak></div>

    {{-- Sidebar --}}
    <x-app-sidebar />

    {{-- Desktop sidebar toggle --}}
    <button @click="sidebarOpen = !sidebarOpen"
            :class="sidebarOpen ? 'left-[272px]' : 'left-4'"
            class="hidden lg:flex fixed top-[22px] z-40 w-7 h-7 rounded-full bg-white border border-rule shadow-card items-center justify-center text-ink-700/40 hover:text-ink-900 hover:bg-ink-50 transition-all duration-200"
            title="Toggle sidebar">
        <svg :class="{ 'rotate-180': !sidebarOpen }" class="w-3.5 h-3.5 transition-transform duration-200 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </button>

    {{-- Main content wrapper --}}
    <div :class="sidebarOpen ? 'lg:ml-shell' : 'lg:ml-0'" class="min-h-screen flex flex-col transition-all duration-200">

        {{-- Mobile top bar --}}
        <header class="sticky top-0 z-10 flex lg:hidden items-center gap-3 px-4 py-3 bg-white/90 backdrop-blur-md border-b border-rule">
            <button @click="sidebarOpen = true" class="w-9 h-9 flex items-center justify-center rounded-lg text-ink-700/60 hover:bg-ink-50 hover:text-ink-900 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-md bg-mark-500 text-ink-900 flex items-center justify-center font-bold text-xs">LR</div>
                <span class="text-sm font-semibold text-ink-900 tracking-tight">Lab Reserve</span>
            </div>
        </header>

        {{-- Topbar slot (optional) --}}
        @isset($topbar)
            <header class="sticky top-0 z-20 bg-bg/85 backdrop-blur-sm border-b border-rule">
                <div class="px-4 lg:px-8 py-3 flex items-center gap-4">
                    {{ $topbar }}
                </div>
            </header>
        @endisset

        {{-- Page heading slot --}}
        @isset($header)
            <header class="px-4 lg:px-8 pt-6 lg:pt-10 pb-0">
                <div class="max-w-content mx-auto">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Main content --}}
        <main class="flex-1 px-4 lg:px-8 py-6 lg:py-8">
            <div class="max-w-content mx-auto">
                {{ $slot }}
            </div>
        </main>

        {{-- Footer (desktop only) --}}
        <footer class="hidden lg:block px-8 py-6 border-t border-rule">
            <div class="max-w-content mx-auto flex items-center justify-between text-[0.7rem] uppercase tracking-label text-ink-700/40 font-semibold">
                <span>UKRIDA · Lab Reserve</span>
                <span>v1.0.0</span>
            </div>
        </footer>

    </div>


    {{-- Toast container --}}
    <div id="toast-root" class="fixed bottom-6 right-4 lg:right-6 z-50 flex flex-col gap-2"></div>

    @stack('scripts')
</body>
</html>
