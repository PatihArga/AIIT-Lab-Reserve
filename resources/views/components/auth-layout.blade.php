<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} — UKRIDA Lab Reserve</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-bg">

<div class="min-h-screen grid lg:grid-cols-[1.05fr_1fr]">

    {{-- LEFT: Brand panel with Lab Photo --}}
    <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden p-14 bg-cover bg-center" style="background-image: url('{{ asset('images/labkom.png') }}');">
        
        {{-- Overlays for contrast and brand colors --}}
        <div class="absolute inset-0 bg-ink-900/70 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-ink-900 via-ink-900/40 to-transparent opacity-90"></div>

        {{-- Yellow mark strip — top-left --}}
        <div class="absolute top-0 left-0 h-1.5 w-32 bg-mark-500 shadow-[0_0_20px_rgba(245,184,0,0.5)] z-20"></div>

        <div class="relative z-10 flex items-center gap-4">
            <img src="{{ asset('images/ukrida_logo.png') }}" alt="UKRIDA Logo" class="h-16 w-auto drop-shadow-lg">
            <div class="border-l border-white/20 pl-4">
                <div class="text-[0.65rem] uppercase tracking-label text-mark-400 font-bold mb-0.5">AIIT</div>
                <div class="text-lg font-semibold tracking-tight text-white/95">Lab Reserve</div>
            </div>
        </div>

        <div class="relative z-10 max-w-md mt-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-[0.65rem] uppercase tracking-label text-white/90 font-semibold mb-6 backdrop-blur-md">
                <span class="w-1.5 h-1.5 rounded-full bg-mark-500 animate-pulse"></span>
                Sistem Reservasi
            </div>
            <h1 class="font-display text-[3.25rem] leading-[1.05] font-bold tracking-tight text-white mb-2">
                Lab Komputer<br>
                <span class="text-mark-400">UKRIDA</span>
            </h1>
        </div>

        <div class="relative z-10 text-[0.65rem] uppercase tracking-label text-white/50 font-semibold mt-12">
            © {{ date('Y') }} · FTI UKRIDA
        </div>
    </aside>

    {{-- RIGHT: Form panel --}}
    <main class="relative flex items-center justify-center p-6 sm:p-12 bg-[#F8F9FA] overflow-hidden">
        {{-- Subtle background decoration for right side --}}
        <div class="absolute top-[-10%] right-[-5%] w-[40vw] h-[40vw] rounded-full bg-mark-500/5 blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-ink-500/5 blur-[120px] pointer-events-none"></div>
        
        <div class="relative w-full max-w-md bg-white/60 backdrop-blur-2xl p-10 sm:p-12 rounded-[2rem] shadow-[0_8px_40px_rgba(15,36,96,0.06),0_1px_3px_rgba(0,0,0,0.02)] border border-white">
            {{ $slot }}
        </div>
    </main>

</div>

</body>
</html>
