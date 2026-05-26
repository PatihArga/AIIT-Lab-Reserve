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
            <label for="gmail" class="form-label">Gmail Admin</label>
            <input
                id="gmail"
                name="gmail"
                type="email"
                value="{{ old('gmail') }}"
                placeholder="nama@gmail.com"
                required
                autofocus
                autocomplete="username"
                class="form-input"
            />
        </div>

        <div class="form-field" x-data="{ show: false }">
            <label for="password" class="form-label">Kata Sandi</label>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    x-bind:type="show ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    class="form-input pr-12"
                    placeholder="••••••••"
                />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center px-4 text-ink-700/50 hover:text-ink-700 transition-colors focus:outline-none">
                    {{-- Eye open icon --}}
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{-- Eye closed icon --}}
                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
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
