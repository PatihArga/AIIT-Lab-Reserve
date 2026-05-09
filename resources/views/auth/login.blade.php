<x-auth-layout title="Masuk">

    {{-- Step indicator --}}
    <div class="flex items-center gap-3 mb-10">
        <div class="step-dot-active">1</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-900">Email</span>
        <div class="step-connector"></div>
        <div class="step-dot-pending">2</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-700/40">Pilih Nama</span>
    </div>

    <div class="page-eyebrow">Masuk</div>
    <h2 class="font-display text-3xl font-bold text-ink-900 tracking-tight">Selamat datang.</h2>
    <p class="mt-3 text-sm text-ink-700/70">
        Masukkan email program studi untuk melanjutkan.
    </p>

    @if ($errors->any())
        <div class="mt-6 rounded-md border border-status-rejected/30 bg-status-rejected/5 p-3">
            @foreach ($errors->all() as $error)
                <p class="text-sm text-status-rejected">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
        @csrf

        <div class="form-field">
            <label for="email" class="form-label">Email Program Studi</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                placeholder="nama@ti.ukrida.ac.id"
                required
                autofocus
                autocomplete="username"
                class="form-input"
            />
            <p class="form-hint">Domain email akan menentukan program studi Anda.</p>
        </div>

        <button type="submit" class="btn-mark btn-lg w-full">
            Lanjutkan
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </button>
    </form>

    <div class="mt-12 pt-6 border-t border-rule space-y-4">
        <p class="text-xs text-ink-700/50">
            Tidak punya akun? <span class="text-ink-700/70 font-medium">Hubungi administrator laboratorium.</span>
        </p>
        <a href="{{ route('admin.login') }}"
           class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg border border-ink-200 bg-white hover:bg-ink-50 hover:border-ink-300 text-sm font-medium text-ink-700 transition-all">
            <svg class="w-4 h-4 text-mark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Masuk sebagai Administrator
        </a>
    </div>

</x-auth-layout>
