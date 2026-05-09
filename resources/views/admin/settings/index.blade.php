<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Sistem"
            title="Pengaturan Lab"
            meta="Konfigurasi operasional laboratorium">
        </x-page-header>
    </x-slot:header>

    <div class="max-w-xl mx-auto">
        <form method="POST" action="{{ route('admin.settings.index') }}">
            @csrf
            @method('PUT')

            <x-section label="Identitas Lab">
                <div class="space-y-4">
                    <div class="form-field">
                        <label class="form-label form-required">Nama Lab</label>
                        <input type="text" name="lab_name" value="Laboratorium Komputer 401" class="form-input" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Email Admin</label>
                        <input type="email" name="admin_email" value="labadmin@ukrida.ac.id" class="form-input" required>
                        <p class="form-hint">Digunakan sebagai pengirim notifikasi email.</p>
                    </div>
                </div>
            </x-section>

            <x-section label="Jam Operasional">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="form-field">
                        <label class="form-label form-required">Jam Buka</label>
                        <select name="operating_start" class="form-select">
                            @for ($h = 6; $h <= 10; $h++)
                                <option value="{{ str_pad($h,2,'0',STR_PAD_LEFT).':00' }}"
                                        {{ $h === 8 ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Jam Tutup</label>
                        <select name="operating_end" class="form-select">
                            @for ($h = 16; $h <= 23; $h++)
                                <option value="{{ str_pad($h,2,'0',STR_PAD_LEFT).':00' }}"
                                        {{ $h === 22 ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label form-required">Hari Operasional</label>
                    <div class="flex gap-2 flex-wrap mt-2">
                        @foreach (['Sen' => 1, 'Sel' => 2, 'Rab' => 3, 'Kam' => 4, 'Jum' => 5, 'Sab' => 6, 'Min' => 0] as $label => $val)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="operating_days[]" value="{{ $val }}"
                                       {{ in_array($val, [1,2,3,4,5,6]) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-9 flex items-center justify-center rounded-md border border-rule
                                            text-xs font-semibold text-ink-700/50
                                            peer-checked:bg-ink-900 peer-checked:text-white peer-checked:border-ink-900
                                            hover:border-ink-300 transition-all cursor-pointer">
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </x-section>

            <x-section label="Batas Sesi">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-field">
                        <label class="form-label form-required">Maks. Durasi Sesi (jam)</label>
                        <input type="number" name="max_session_hours" value="4" min="1" max="8"
                               class="form-input font-mono" required>
                        <p class="form-hint">Durasi maksimum per permintaan reservasi.</p>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Buffer Antar Sesi (menit)</label>
                        <input type="number" name="buffer_minutes" value="15" min="0" max="60" step="5"
                               class="form-input font-mono" required>
                        <p class="form-hint">Jeda wajib antara dua sesi berturutan.</p>
                    </div>
                </div>
            </x-section>

            <x-section label="Google Calendar">
                <div class="space-y-4">
                    <div class="form-field">
                        <label class="form-label">Calendar ID</label>
                        <input type="text" name="google_calendar_id"
                               value="lab401@group.calendar.google.com"
                               class="form-input font-mono text-xs">
                        <p class="form-hint">ID kalender Google yang digunakan untuk sinkronisasi event.</p>
                    </div>
                    <div class="p-3 rounded-lg bg-ink-50 border border-rule flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-status-approved shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs text-ink-700/70">Service Account terhubung · credentials.json valid</span>
                    </div>
                </div>
            </x-section>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <p class="text-xs text-ink-700/40">Perubahan berlaku segera setelah disimpan</p>
                <button type="submit" class="btn-mark">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Pengaturan
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
