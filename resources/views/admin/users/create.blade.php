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
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <x-section label="Informasi Akun">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-input @error('name') border-status-rejected @enderror"
                               placeholder="cth. Dr. Budi Santoso, M.Kom." required>
                        @error('name') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Email Institusi</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-input @error('email') border-status-rejected @enderror"
                               placeholder="nama@ukrida.ac.id" required>
                        <p class="form-hint">Email harus unik sebagai identitas akun. Login dosen dilakukan melalui Gmail program studi.</p>
                        @error('email') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Program Studi</label>
                        <select name="study_program_id" required
                                class="form-select @error('study_program_id') border-status-rejected @enderror">
                            <option value="" disabled @selected(! old('study_program_id'))>Pilih program studi…</option>
                            @foreach ($studyPrograms as $sp)
                                <option value="{{ $sp->id }}" @selected(old('study_program_id') == $sp->id)>{{ $sp->name }}</option>
                            @endforeach
                        </select>
                        @error('study_program_id') <p class="text-xs text-status-rejected mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-rule bg-white cursor-pointer hover:bg-ink-50/50 has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                               class="w-4 h-4 accent-ink-700 rounded">
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
