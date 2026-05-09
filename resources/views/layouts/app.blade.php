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
<body x-data="{ sidebarOpen: true }" class="font-sans antialiased min-h-screen bg-bg">

    {{-- Sidebar collapse/expand toggle --}}
    <button @click="sidebarOpen = !sidebarOpen"
            :class="sidebarOpen ? 'left-[272px]' : 'left-4'"
            class="fixed top-[22px] z-40 w-7 h-7 rounded-full bg-white border border-rule shadow-card flex items-center justify-center text-ink-700/40 hover:text-ink-900 hover:bg-ink-50 transition-all duration-200"
            title="Toggle sidebar">
        <svg :class="{ 'rotate-180': !sidebarOpen }" class="w-3.5 h-3.5 transition-transform duration-200 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </button>

    <x-app-sidebar />

    <div :class="sidebarOpen ? 'ml-shell' : 'ml-0'" class="min-h-screen flex flex-col transition-all duration-200">

        {{-- Topbar (slim, only when needed) --}}
        @isset($topbar)
            <header class="sticky top-0 z-20 bg-bg/85 backdrop-blur-sm border-b border-rule">
                <div class="px-8 py-3 flex items-center gap-4">
                    {{ $topbar }}
                </div>
            </header>
        @endisset

        {{-- Page heading slot --}}
        @isset($header)
            <header class="px-8 pt-10 pb-0">
                <div class="max-w-content mx-auto">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Main content --}}
        <main class="flex-1 px-8 py-8">
            <div class="max-w-content mx-auto">
                {{ $slot }}
            </div>
        </main>

        {{-- Footer --}}
        <footer class="px-8 py-6 border-t border-rule">
            <div class="max-w-content mx-auto flex items-center justify-between text-[0.7rem] uppercase tracking-label text-ink-700/40 font-semibold">
                <span>UKRIDA · Lab Reserve</span>
                <span>v1.0.0</span>
            </div>
        </footer>

    </div>

    {{-- Toast container --}}
    <div id="toast-root" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>

    @stack('scripts')
</body>
</html>
