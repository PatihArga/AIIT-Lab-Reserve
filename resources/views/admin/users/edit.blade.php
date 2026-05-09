<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Pengguna & Tim"
            title="Edit Pengguna">
            <x-slot:actions>
                <a href="{{ route('admin.users.index') }}" class="btn-ghost btn-sm">← Kembali</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-xl mx-auto">
        <form method="POST" action="{{ route('admin.users.index') }}">
            @csrf
            @method('PUT')

            <x-section label="Informasi Akun">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Nama Lengkap</label>
                        <input type="text" name="name" value="Dr. Budi Santoso, M.Kom." class="form-input" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Email Institusi</label>
                        <input type="email" name="email" value="budi@ukrida.ac.id" class="form-input" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Program Studi</label>
                        <select name="study_program_id" class="form-select" required>
                            <option value="1" selected>Teknik Informatika</option>
                            <option value="2">Sistem Informasi</option>
                            <option value="3">Teknik Elektro</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white cursor-pointer hover:bg-ink-50/50 has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 accent-ink-700 rounded">
                        <div>
                            <div class="text-sm font-medium text-ink-900">Akun Aktif</div>
                            <div class="text-xs text-ink-700/50">Nonaktifkan untuk memblokir akses login</div>
                        </div>
                    </label>

                </div>
            </x-section>

            <x-section label="Reset Password">
                <p class="text-sm text-ink-700/60 mb-4">Kosongkan jika tidak ingin mengubah password.</p>
                <div class="space-y-4">
                    <div class="form-field">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-input" placeholder="Minimal 8 karakter">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-input">
                    </div>
                </div>
            </x-section>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Batal</a>
                <button type="submit" class="btn-mark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
