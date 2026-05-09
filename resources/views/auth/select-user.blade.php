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
