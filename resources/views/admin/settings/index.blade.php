<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Sistem"
            title="Pengaturan Lab"
            meta="Konfigurasi operasional laboratorium">
        </x-page-header>
    </x-slot:header>

    <div class="max-w-xl mx-auto">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-section label="Identitas Lab">
                <div class="space-y-4">
                    <div class="form-field">
                        <label class="form-label form-required">Nama Lab</label>
                        <input type="text" name="lab_name" value="{{ old('lab_name', $settings['lab_name']) }}" class="form-input" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Email Admin</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email', $settings['admin_email']) }}" class="form-input" required>
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
                                        {{ old('operating_start', $settings['operating_start']) === (str_pad($h,2,'0',STR_PAD_LEFT).':00') ? 'selected' : '' }}>
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
                                        {{ old('operating_end', $settings['operating_end']) === (str_pad($h,2,'0',STR_PAD_LEFT).':00') ? 'selected' : '' }}>
                                    {{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label form-required">Hari Operasional</label>
                    @php $selectedDays = old('operating_days', $settings['operating_days']); @endphp
                    <div class="flex gap-2 flex-wrap mt-2">
                        @foreach (['Sen' => 1, 'Sel' => 2, 'Rab' => 3, 'Kam' => 4, 'Jum' => 5, 'Sab' => 6, 'Min' => 7] as $label => $val)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="operating_days[]" value="{{ $val }}"
                                       {{ in_array($val, $selectedDays) ? 'checked' : '' }}
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
                        <input type="number" name="max_session_hours" value="{{ old('max_session_hours', $settings['max_session_hours']) }}" min="1" max="8"
                               class="form-input font-mono" required>
                        <p class="form-hint">Durasi maksimum per permintaan reservasi.</p>
                    </div>
                    <div class="form-field">
                        <label class="form-label form-required">Buffer Antar Sesi (menit)</label>
                        <input type="number" name="buffer_minutes" value="{{ old('buffer_minutes', $settings['buffer_minutes']) }}" min="0" max="60" step="5"
                               class="form-input font-mono" required>
                        <p class="form-hint">Jeda wajib antara dua sesi berturutan.</p>
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
