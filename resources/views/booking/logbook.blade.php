<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi Baru"
            title="Informasi Kegiatan">
            <x-slot:actions>
                <x-step-indicator
                    :steps="['Pilih Jadwal', 'Informasi', 'Tinjau']"
                    :current="2" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto">

        <p class="text-sm text-ink-700/60 mb-8">
            Lengkapi informasi kegiatan yang akan dilakukan. Data ini ditinjau oleh admin sebagai dasar persetujuan.
        </p>

        @if (session('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('booking.review') }}" method="GET">

            @php
                $d = $logbookDraft ?? [];
                $oldCat   = old('category', $d['category'] ?? '');
                $oldCp    = old('checkpoint_progress', $d['checkpoint_progress'] ?? '');
                $oldCourse= old('related_course', $d['related_course'] ?? '');
                $oldSup   = old('supervisor_name', $d['supervisor_name'] ?? '');
                $oldNet   = old('needs_internet', $d['needs_internet'] ?? false);
            @endphp

            <x-section label="Informasi">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Kategori Kegiatan</label>
                        <select name="category" class="form-select" required>
                            <option value="" disabled {{ $oldCat === '' ? 'selected' : '' }}>Pilih kategori…</option>
                            <option value="penelitian"       {{ $oldCat === 'penelitian' ? 'selected' : '' }}>Penelitian</option>
                            <option value="project_akademik" {{ $oldCat === 'project_akademik' ? 'selected' : '' }}>Project Akademik</option>
                            <option value="praktikum"        {{ $oldCat === 'praktikum' ? 'selected' : '' }}>Praktikum</option>
                            <option value="tugas_akhir"      {{ $oldCat === 'tugas_akhir' ? 'selected' : '' }}>Tugas Akhir / Skripsi</option>
                            <option value="lainnya"          {{ $oldCat === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Checkpoint / Alasan Peminjaman</label>
                        <textarea name="checkpoint_progress" class="form-textarea" rows="4"
                                  placeholder="Jelaskan secara singkat keperluan penggunaan laboratorium pada sesi ini…"
                                  required>{{ $oldCp }}</textarea>
                        <p class="form-hint">
                            Contoh: "Pengumpulan data eksperimen sensor suhu — sesi pengambilan data tahap 2." (min. 10 karakter)
                        </p>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Mata Kuliah Terkait</label>
                        <input type="text" name="related_course" class="form-input"
                               placeholder="cth. Kecerdasan Buatan, Rekayasa Perangkat Lunak"
                               value="{{ $oldCourse }}"
                               required>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Nama Pembimbing <span class="text-ink-700/40 font-normal">(opsional)</span></label>
                        <input type="text" name="supervisor_name" class="form-input"
                               placeholder="cth. Dr. Budi Santoso"
                               value="{{ $oldSup }}">
                    </div>

                    <label class="flex items-center gap-3 p-4 rounded-lg border border-rule bg-white cursor-pointer
                                  hover:bg-ink-50/50 has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                        <input type="checkbox" name="needs_internet" value="1"
                               class="w-4 h-4 accent-ink-700 rounded shrink-0"
                               {{ $oldNet ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-medium text-ink-900">Membutuhkan koneksi internet (Wi-Fi)</div>
                            <div class="text-xs text-ink-700/50">Centang jika kegiatan memerlukan akses internet selama sesi</div>
                        </div>
                    </label>

                </div>
            </x-section>

            <div class="flex items-center justify-between pt-6 border-t border-rule">
                <a href="{{ route('booking.schedule') }}" class="btn-ghost">
                    ← Kembali
                </a>
                <button type="submit" class="btn-mark btn-lg">
                    Tinjau Reservasi
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
