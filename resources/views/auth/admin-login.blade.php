<x-auth-layout title="Masuk Admin">

    {{-- Admin badge --}}
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-mark-500/10 border border-mark-500/30 mb-8">
        <svg class="w-3.5 h-3.5 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span class="text-[0.65rem] uppercase tracking-label font-semibold text-mark-700">Portal Admin</span>
    </div>

    <div class="page-eyebrow">Masuk</div>
    <h2 class="font-display text-3xl font-bold text-ink-900 tracking-tight">Administrator.</h2>
    <p class="mt-3 text-sm text-ink-700/70">
        Akses khusus pengelola laboratorium. Masukkan kredensial admin Anda.
    </p>

    @if ($errors->any())
        <div class="mt-6 rounded-md border border-status-rejected/30 bg-status-rejected/5 p-3">
            @foreach ($errors->all() as $error)
                <p class="text-sm text-status-rejected">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.authenticate') }}" class="mt-8 space-y-5">
        @csrf

        <div class="form-field">
            <label for="email" class="form-label">Email Admin</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                placeholder="admin@ukrida.ac.id"
                required
                autofocus
                autocomplete="username"
                class="form-input"
            />
        </div>

        <div class="form-field">
            <label for="password" class="form-label">Kata Sandi</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="form-input"
            />
        </div>

        <div class="flex items-center gap-2 pt-1">
            <input type="checkbox" name="remember" id="remember"
                   class="w-4 h-4 accent-ink-700 rounded">
            <label for="remember" class="text-sm text-ink-700/70 cursor-pointer">Ingat saya</label>
        </div>

        <button type="submit" class="btn-mark btn-lg w-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Masuk sebagai Admin
        </button>
    </form>

    <div class="mt-10 pt-6 border-t border-rule">
        <a href="{{ route('login') }}"
           class="flex items-center gap-2 text-sm text-ink-700/60 hover:text-ink-900 transition-colors group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Masuk sebagai Dosen / Tim Mahasiswa
        </a>
    </div>

</x-auth-layout>
