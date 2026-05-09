<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Permintaan Reservasi"
            title="Tinjauan Permintaan">
            <x-slot:actions>
                <a href="{{ route('admin.requests.index') }}" class="btn-ghost btn-sm">← Kembali</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-10">

        {{-- LEFT: Full detail --}}
        <div class="space-y-10">

            {{-- Booking summary --}}
            <x-section label="Informasi Reservasi">
                <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden">
                    <div class="px-6 py-4 bg-ink-900 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] uppercase tracking-label text-ink-100/50 font-semibold">Kode Reservasi</div>
                            <div class="font-mono text-white font-semibold text-base mt-0.5">LAB-0042</div>
                        </div>
                        <x-badge status="pending" />
                    </div>
                    <div class="divide-y divide-rule">
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pemohon</div>
                            <div class="col-span-2">
                                <div class="font-medium text-ink-900">Tim Alpha</div>
                                <div class="text-xs text-ink-700/50">PIC: Dr. Budi Santoso</div>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Jenis</div>
                            <div class="col-span-2 text-sm font-medium text-ink-900">Komputer + Ruang (Seluruh Lab)</div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Tanggal</div>
                            <div class="col-span-2">
                                <div class="mono-data">12 Mei 2026</div>
                                <div class="mono-code mt-0.5">Selasa</div>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Waktu</div>
                            <div class="col-span-2">
                                <span class="mono-data">09:00</span>
                                <span class="mono-code mx-1">–</span>
                                <span class="mono-data">12:00</span>
                                <span class="mono-code ml-2">(3 jam)</span>
                            </div>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-3 gap-4">
                            <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Diajukan</div>
                            <div class="col-span-2 mono-code">08 Mei 2026 · 14:32</div>
                        </div>
                    </div>
                </div>
            </x-section>

            {{-- Computer grid --}}
            <x-section label="Unit Komputer">
                @php
                    $computers = collect(range(1, 9))->map(fn($n) => (object)[
                        'id'     => $n,
                        'label'  => 'PC-' . str_pad($n, 2, '0', STR_PAD_LEFT),
                        'status' => $n === 9 ? 'maintenance' : 'online',
                    ]);
                @endphp
                <x-computer-grid :computers="$computers" />
                <p class="form-hint mt-2">Seluruh 8 unit aktif akan digunakan dalam sesi ini. PC-09 dalam pemeliharaan.</p>
            </x-section>

            {{-- Logbook --}}
            <x-section label="Logbook Kegiatan">
                <div class="bg-white border border-rule rounded-xl shadow-card divide-y divide-rule overflow-hidden">
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Kategori</div>
                        <div class="col-span-2 text-sm font-medium text-ink-900">Penelitian</div>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Checkpoint</div>
                        <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed">
                            Implementasi algoritma pencarian pada dataset sensor. Sesi ini akan menyelesaikan modul pengumpulan data dan validasi awal hasil pengukuran.
                        </div>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Mata Kuliah</div>
                        <div class="col-span-2 text-sm text-ink-900">Kecerdasan Buatan</div>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Pembimbing</div>
                        <div class="col-span-2 text-sm text-ink-900">Dr. Budi Santoso</div>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Software</div>
                        <div class="col-span-2 text-sm text-ink-900">Python 3.11, Jupyter Lab, TensorFlow 2.x</div>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Target Sesi</div>
                        <div class="col-span-2 text-sm text-ink-700/80">Menyelesaikan preprocessing dataset dan mendapatkan akurasi baseline ≥ 80%.</div>
                    </div>
                    <div class="px-6 py-4 flex items-center gap-3">
                        <span class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50 mr-1">Kebutuhan</span>
                        <span class="badge-outline text-[10px]">Internet</span>
                        <span class="badge-outline text-[10px]">Instalasi</span>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Prioritas</div>
                        <div class="col-span-2"><x-badge status="pending">Normal</x-badge></div>
                    </div>
                </div>
            </x-section>

        </div>

        {{-- RIGHT: Approve / Reject panel --}}
        <div class="space-y-8">

            {{-- Conflict check --}}
            <x-section label="Cek Konflik">
                <div class="p-3 rounded-lg bg-status-approved/8 border border-status-approved/20 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-status-approved shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-status-approved">Tidak ada konflik jadwal</span>
                </div>
                <p class="text-xs text-ink-700/50 mt-2">Slot 12 Mei 2026 · 09:00–12:00 masih kosong.</p>
            </x-section>

            {{-- Approve action --}}
            <x-section label="Setujui">
                <p class="text-sm text-ink-700/60 mb-4">
                    Menyetujui akan mengunci slot, mengirim email notifikasi ke pemohon, dan membuat event di Google Calendar.
                </p>
                <button class="w-full btn-mark justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Setujui Permintaan
                </button>
            </x-section>

            {{-- Reject action --}}
            <x-section label="Tolak">
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full btn-danger btn-sm justify-center mb-3">
                        Tolak Permintaan
                    </button>
                    <div x-show="open" x-transition class="space-y-3">
                        <div class="form-field">
                            <label class="form-label form-required">Alasan Penolakan</label>
                            <textarea class="form-textarea" rows="3"
                                      placeholder="Jelaskan alasan penolakan kepada pemohon…"></textarea>
                        </div>
                        <button class="w-full btn-danger justify-center">
                            Konfirmasi Penolakan
                        </button>
                    </div>
                </div>
            </x-section>

        </div>

    </div>

</x-app-layout>
