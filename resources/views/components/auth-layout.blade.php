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
        <div class="absolute inset-0 bg-grid-light pointer-events-none"></div>

        {{-- Yellow mark strip — top-left --}}
        <div class="absolute top-0 left-0 h-1 w-24 bg-mark-500"></div>

        {{-- Atmospheric blobs --}}
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-ink-600/30 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-72 h-72 rounded-full bg-mark-500/10 blur-3xl"></div>

        <div class="relative z-10 flex items-center gap-3">
            <div class="w-11 h-11 rounded-md bg-mark-500 text-ink-900 flex items-center justify-center font-bold text-lg">
                LR
            </div>
            <div>
                <div class="text-[0.65rem] uppercase tracking-label text-mark-500/90 font-semibold">UKRIDA</div>
                <div class="text-base font-semibold tracking-tight">Lab Reserve</div>
            </div>
        </div>

        <div class="relative z-10 max-w-md">
            <div class="text-[0.7rem] uppercase tracking-label text-white/50 font-semibold mb-4">Sistem Reservasi</div>
            <h1 class="font-display text-[2.75rem] leading-[1.05] font-bold tracking-tight">
                Penjadwalan<br>
                yang <span class="relative inline-block">
                    <span class="relative z-10">tepat</span>
                    <span class="absolute inset-x-0 bottom-1 h-3 bg-mark-500/40 -z-0"></span>
                </span>,<br>
                untuk lab yang sibuk.
            </h1>
            <p class="mt-6 text-sm text-white/60 leading-relaxed max-w-sm">
                Reservasi terpadu untuk dosen dan tim mahasiswa Universitas Kristen Krida Wacana — satu lab, sembilan unit, satu dasbor.
            </p>
        </div>

        <div class="relative z-10 grid grid-cols-3 gap-6 max-w-md pt-8 border-t border-white/10">
            <div>
                <div class="font-mono text-2xl text-white font-semibold">9</div>
                <div class="text-[0.65rem] uppercase tracking-label text-white/40 mt-1">Unit</div>
            </div>
            <div>
                <div class="font-mono text-2xl text-mark-500 font-semibold">14h</div>
                <div class="text-[0.65rem] uppercase tracking-label text-white/40 mt-1">Per Hari</div>
            </div>
            <div>
                <div class="font-mono text-2xl text-white font-semibold">6</div>
                <div class="text-[0.65rem] uppercase tracking-label text-white/40 mt-1">Hari/Mgg</div>
            </div>
        </div>

        <div class="relative z-10 text-[0.65rem] uppercase tracking-label text-white/30 font-semibold">
            © {{ date('Y') }} · UKRIDA
        </div>
    </aside>

    {{-- RIGHT: Form panel --}}
    <main class="flex items-center justify-center p-6 sm:p-12 bg-bg">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </main>

</div>

</body>
</html>
