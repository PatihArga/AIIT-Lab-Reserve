<x-auth-layout title="Pilih Akun">

    {{-- Step indicator --}}
    <div class="flex items-center gap-3 mb-10">
        <div class="step-dot-complete">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-700/50">Email</span>
        <div class="step-connector-complete"></div>
        <div class="step-dot-active">2</div>
        <span class="text-[0.7rem] uppercase tracking-label font-semibold text-ink-900">Pilih Nama</span>
    </div>

    <div class="page-eyebrow">{{ $program->name }}</div>
    <h2 class="font-display text-3xl font-bold text-ink-900 tracking-tight">Pilih akun Anda.</h2>
    <p class="mt-3 text-sm text-ink-700/70">
        <span class="mono-data">{{ count($users) }}</span> akun tersedia di program studi ini.
    </p>

    @if ($errors->any())
        <div class="mt-6 rounded-md border border-status-rejected/30 bg-status-rejected/5 p-3">
            @foreach ($errors->all() as $error)
                <p class="text-sm text-status-rejected">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.authenticate') }}" class="mt-8 space-y-6">
        @csrf

        <div class="form-field">
            <label for="user_id" class="form-label">Nama</label>
            <select id="user_id" name="user_id" required class="form-select">
                <option value="" disabled {{ old('user_id') ? '' : 'selected' }}>— Pilih nama Anda —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                        @if ($user->role === 'team') · Tim
                        @elseif ($user->role === 'admin') · Admin
                        @else · Dosen
                        @endif
                    </option>
                @endforeach
            </select>
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
                placeholder="••••••••"
            />
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-700/80 cursor-pointer">
            <input type="checkbox" name="remember" value="1"
                   class="rounded border-rule-strong text-ink-700 focus:ring-ink-500 focus:ring-offset-0">
            <span>Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="btn-mark btn-lg w-full">
            Masuk
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
        </button>

        <a href="{{ route('login') }}"
           class="block text-center text-xs uppercase tracking-label font-semibold text-ink-700/50 hover:text-ink-700 transition-colors">
            ← Gunakan email lain
        </a>
    </form>

</x-auth-layout>
