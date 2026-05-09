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

    {{-- LEFT: Brand panel --}}
    <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-ink-900 text-white p-14">
        {{-- Blueprint grid texture --}}
        <div class="absolute inset-0 bg-grid-light pointer-events-none opacity-50"></div>

        {{-- Yellow mark strip — top-left --}}
        <div class="absolute top-0 left-0 h-1.5 w-32 bg-mark-500 shadow-[0_0_20px_rgba(245,184,0,0.5)]"></div>

        {{-- Atmospheric blobs with subtle animation --}}
        <div class="absolute -top-32 -right-32 w-[30rem] h-[30rem] rounded-full bg-ink-500/30 blur-[80px] animate-pulse-slow"></div>
        <div class="absolute bottom-0 right-0 w-[25rem] h-[25rem] rounded-full bg-mark-500/15 blur-[80px] animate-pulse-slow" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/4 -translate-x-1/2 -translate-y-1/2 w-[40rem] h-[40rem] rounded-full bg-ink-400/10 blur-[100px] animate-float pointer-events-none"></div>

        <div class="relative z-10 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-mark-400 to-mark-600 shadow-lg shadow-mark-500/30 text-ink-900 flex items-center justify-center font-bold text-xl ring-1 ring-white/20">
                LR
            </div>
            <div>
                <div class="text-[0.65rem] uppercase tracking-label text-mark-400 font-bold mb-0.5">UKRIDA</div>
                <div class="text-lg font-semibold tracking-tight text-white/95">Lab Reserve</div>
            </div>
        </div>

        <div class="relative z-10 max-w-md">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-[0.65rem] uppercase tracking-label text-white/70 font-semibold mb-6 backdrop-blur-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-mark-500 animate-pulse"></span>
                Sistem Reservasi
            </div>
            <h1 class="font-display text-[3.25rem] leading-[1.05] font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-br from-white to-white/70">
                Penjadwalan<br>
                yang <span class="relative inline-block text-white">
                    <span class="relative z-10">tepat</span>
                    <span class="absolute inset-x-0 bottom-2 h-4 bg-mark-500/40 -z-0 -rotate-1 scale-105"></span>
                </span>,<br>
                untuk lab yang sibuk.
            </h1>
            <p class="mt-6 text-base text-white/60 leading-relaxed max-w-sm font-medium">
                Reservasi terpadu untuk dosen dan tim mahasiswa Universitas Kristen Krida Wacana — satu lab, sembilan unit, satu dasbor.
            </p>
        </div>

        <div class="relative z-10 grid grid-cols-3 gap-6 max-w-md pt-8 border-t border-white/10">
            <div class="group">
                <div class="font-mono text-3xl text-white font-semibold transition-transform group-hover:-translate-y-1">9</div>
                <div class="text-[0.65rem] uppercase tracking-label text-white/40 mt-2 font-medium">Unit</div>
            </div>
            <div class="group">
                <div class="font-mono text-3xl text-mark-400 font-semibold transition-transform group-hover:-translate-y-1">14h</div>
                <div class="text-[0.65rem] uppercase tracking-label text-white/40 mt-2 font-medium">Per Hari</div>
            </div>
            <div class="group">
                <div class="font-mono text-3xl text-white font-semibold transition-transform group-hover:-translate-y-1">6</div>
                <div class="text-[0.65rem] uppercase tracking-label text-white/40 mt-2 font-medium">Hari/Mgg</div>
            </div>
        </div>

        <div class="relative z-10 text-[0.65rem] uppercase tracking-label text-white/30 font-semibold">
            © {{ date('Y') }} · UKRIDA
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
