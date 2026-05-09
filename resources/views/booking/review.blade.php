<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Reservasi Baru"
            title="Tinjau & Kirim">
            <x-slot:actions>
                <x-step-indicator
                    :steps="['Pilih Tipe', 'Jadwal', 'Informasi', 'Tinjau']"
                    :current="4" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto">

        <p class="text-sm text-ink-700/60 mb-8">
            Periksa kembali semua detail sebelum mengirim permintaan. Setelah dikirim, data tidak dapat diubah tanpa persetujuan admin.
        </p>

        {{-- Summary card --}}
        <div class="bg-white border border-rule rounded-xl shadow-card overflow-hidden mb-6">

            {{-- Header strip --}}
            <div class="px-6 py-4 bg-ink-900 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase tracking-label text-ink-100/50 font-semibold">Draf Permintaan</div>
                    <div class="font-mono text-white font-semibold text-sm mt-0.5">LAB-XXXX</div>
                </div>
                <x-badge status="draft" />
            </div>

            {{-- Detail rows --}}
            <div class="divide-y divide-rule">

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Jenis</div>
                    <div class="col-span-2 text-sm font-medium text-ink-900">Komputer Saja</div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Tanggal</div>
                    <div class="col-span-2">
                        <div class="mono-data text-ink-900">12 Mei 2026</div>
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
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Unit Dipilih</div>
                    <div class="col-span-2 flex flex-wrap gap-1.5">
                        @foreach (['PC-01', 'PC-03', 'PC-05'] as $pc)
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-ink-50 border border-rule-strong text-ink-900 font-semibold">
                                {{ $pc }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Kategori</div>
                    <div class="col-span-2 text-sm text-ink-900">Penelitian</div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Alasan</div>
                    <div class="col-span-2 text-sm text-ink-700/80 leading-relaxed">
                        Pengumpulan data eksperimen sensor suhu — sesi pengambilan data tahap 2.
                    </div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Mata Kuliah</div>
                    <div class="col-span-2 text-sm text-ink-900">Kecerdasan Buatan</div>
                </div>

                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                    <div class="text-[11px] uppercase tracking-label font-semibold text-ink-700/50">Internet</div>
                    <div class="col-span-2">
                        <span class="badge-approved text-[10px]">Dibutuhkan</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- Notice --}}
        <div class="flex gap-3 p-4 rounded-lg bg-mark-50 border border-mark-300/40 mb-8">
            <svg class="w-5 h-5 text-mark-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-ink-700/80">
                Permintaan akan masuk ke antrean tinjauan admin. Anda akan menerima notifikasi email setelah disetujui atau ditolak. Proses biasanya memakan waktu 1×24 jam kerja.
            </p>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-rule">
            <a href="{{ route('booking.logbook') }}" class="btn-ghost">
                ← Kembali
            </a>
            <button type="submit" class="btn-mark btn-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Kirim Permintaan
            </button>
        </div>

    </div>

</x-app-layout>
