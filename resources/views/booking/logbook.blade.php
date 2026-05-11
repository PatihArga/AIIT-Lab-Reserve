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

        <form action="{{ route('booking.review') }}" method="GET">

            <x-section label="Informasi">
                <div class="space-y-5">

                    <div class="form-field">
                        <label class="form-label form-required">Kategori Kegiatan</label>
                        <select name="category" class="form-select" required>
                            <option value="" disabled selected>Pilih kategori…</option>
                            <option value="penelitian">Penelitian</option>
                            <option value="project_akademik">Project Akademik</option>
                            <option value="praktikum">Praktikum</option>
                            <option value="tugas_akhir">Tugas Akhir / Skripsi</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Alasan Peminjaman</label>
                        <textarea name="reason" class="form-textarea" rows="4"
                                  placeholder="Jelaskan secara singkat keperluan penggunaan laboratorium pada sesi ini…"
                                  required></textarea>
                        <p class="form-hint">
                            Contoh: "Pengumpulan data eksperimen sensor suhu — sesi pengambilan data tahap 2."
                        </p>
                    </div>

                    <div class="form-field">
                        <label class="form-label form-required">Mata Kuliah Terkait</label>
                        <input type="text" name="related_course" class="form-input"
                               placeholder="cth. Kecerdasan Buatan, Rekayasa Perangkat Lunak"
                               required>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Nama Pembimbing <span class="text-ink-700/40 font-normal">(opsional)</span></label>
                        <input type="text" name="supervisor_name" class="form-input"
                               placeholder="cth. Dr. Budi Santoso">
                    </div>

                    <label class="flex items-center gap-3 p-4 rounded-lg border border-rule bg-white cursor-pointer
                                  hover:bg-ink-50/50 has-[:checked]:border-ink-700 has-[:checked]:bg-ink-50/40 transition-all">
                        <input type="checkbox" name="needs_internet" value="1"
                               class="w-4 h-4 accent-ink-700 rounded shrink-0">
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
