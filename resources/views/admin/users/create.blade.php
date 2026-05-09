<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Pengguna & Tim"
            title="Tambah Akun Dosen">
            <x-slot:actions>
                <a href="{{ route('admin.users.index') }}" class="btn-ghost btn-sm">← Kembali</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-xl mx-auto">
        <form method="POST" action="{{ route('admin.users.index') }}">
            @csrf

            <x-section label="Informasi Akun">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Nama Lengkap</label>
                        <input type="text" name="name" class="form-input" placeholder="cth. Dr. Budi Santoso, M.Kom." required>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Email Institusi</label>
                        <input type="email" name="email" class="form-input" placeholder="nama@ukrida.ac.id" required>
                        <p class="form-hint">Domain email menentukan program studi secara otomatis.</p>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Program Studi</label>
                        <select name="study_program_id" class="form-select" required>
                            <option value="" disabled selected>Pilih program studi…</option>
                            <option value="1">Teknik Informatika</option>
                            <option value="2">Sistem Informasi</option>
                            <option value="3">Teknik Elektro</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Password Awal</label>
                        <input type="password" name="password" class="form-input" placeholder="Minimal 8 karakter" required>
                        <p class="form-hint">Dosen dapat mengubah password setelah login pertama kali.</p>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white cursor-pointer hover:bg-ink-50/50 has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 accent-ink-700 rounded">
                        <div>
                            <div class="text-sm font-medium text-ink-900">Aktifkan akun segera</div>
                            <div class="text-xs text-ink-700/50">Akun nonaktif tidak dapat login</div>
                        </div>
                    </label>

                </div>
            </x-section>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Batal</a>
                <button type="submit" class="btn-mark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Buat Akun Dosen
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
