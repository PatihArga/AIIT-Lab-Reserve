<x-app-layout>

    <x-slot:header>
        <x-page-header
            eyebrow="Sistem"
            title="Audit Log"
            meta="Riwayat semua aktivitas sistem">
            <x-slot:actions>
                <button class="btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Ekspor Log
                </button>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @php
        $logs = [
            ['time'=>'08 Mei 2026 · 16:42','user'=>'Admin Lab','action'=>'booking.approved','target'=>'LAB-0040','desc'=>'Reservasi disetujui','color'=>'bg-status-approved'],
            ['time'=>'08 Mei 2026 · 14:32','user'=>'Tim Alpha','action'=>'booking.submitted','target'=>'LAB-0042','desc'=>'Permintaan baru dikirim','color'=>'bg-mark-500'],
            ['time'=>'07 Mei 2026 · 16:42','user'=>'Admin Lab','action'=>'computer.maintenance','target'=>'PC-07','desc'=>'Unit ditandai untuk pemeliharaan','color'=>'bg-ink-700/30'],
            ['time'=>'06 Mei 2026 · 11:15','user'=>'Admin Lab','action'=>'booking.rejected','target'=>'LAB-0038','desc'=>'Reservasi ditolak — bentrok jadwal praktikum','color'=>'bg-status-rejected'],
            ['time'=>'05 Mei 2026 · 09:00','user'=>'Dr. Siti Hartati','action'=>'user.login','target'=>'—','desc'=>'Login berhasil','color'=>'bg-ink-700/20'],
            ['time'=>'04 Mei 2026 · 14:00','user'=>'Admin Lab','action'=>'user.created','target'=>'Tim Gamma','desc'=>'Akun tim baru dibuat','color'=>'bg-status-review'],
            ['time'=>'03 Mei 2026 · 10:30','user'=>'Dr. Maria Lestari','action'=>'booking.logbook.updated','target'=>'LAB-0031','desc'=>'Logbook diperbarui (checkpoint)','color'=>'bg-[#2eb8a0]'],
            ['time'=>'02 Mei 2026 · 08:15','user'=>'Admin Lab','action'=>'settings.updated','target'=>'lab_settings','desc'=>'Pengaturan jam operasi diubah','color'=>'bg-ink-700/20'],
        ];
    @endphp

    {{-- Filter bar --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <select class="form-select py-1.5 text-xs w-44">
            <option>Semua aksi</option>
            <option>booking.submitted</option>
            <option>booking.approved</option>
            <option>booking.rejected</option>
            <option>computer.maintenance</option>
            <option>user.created</option>
            <option>user.login</option>
            <option>settings.updated</option>
        </select>
        <select class="form-select py-1.5 text-xs w-44">
            <option>Semua pengguna</option>
            <option>Admin Lab</option>
            <option>Tim Alpha</option>
            <option>Dr. Siti Hartati</option>
            <option>Dr. Maria Lestari</option>
        </select>
        <div class="flex items-center gap-2">
            <input type="date" class="form-input py-1.5 text-xs w-36">
            <span class="text-ink-700/40 text-sm">–</span>
            <input type="date" class="form-input py-1.5 text-xs w-36">
        </div>
        <div class="ml-auto">
            <input type="search" placeholder="Cari…" class="form-input py-1.5 text-xs w-44">
        </div>
    </div>

    {{-- Log list --}}
    <div class="bg-white border border-rule rounded-xl shadow-card divide-y divide-rule overflow-hidden">
        @foreach ($logs as $log)
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-ink-50/30 transition-colors">
                <div class="flex flex-col items-center mt-1 shrink-0">
                    <span class="w-2 h-2 rounded-full {{ $log['color'] }}"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="font-mono text-[11px] font-semibold text-ink-700/60 bg-ink-50 border border-rule-strong rounded px-1.5 py-0.5">
                                {{ $log['action'] }}
                            </span>
                            <span class="text-sm text-ink-900 ml-2">{{ $log['desc'] }}</span>
                        </div>
                        <span class="mono-code text-ink-700/40 shrink-0">{{ $log['time'] }}</span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-3 text-xs text-ink-700/50">
                        <span>oleh <span class="font-medium text-ink-700/70">{{ $log['user'] }}</span></span>
                        @if ($log['target'] !== '—')
                            <span>·</span>
                            <span class="mono-data text-ink-700/60">{{ $log['target'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between mt-4 text-xs text-ink-700/50">
        <span>Menampilkan 8 dari 247 entri</span>
        <div class="flex gap-1">
            <button class="px-3 py-1.5 rounded border border-rule bg-white text-ink-700/50 cursor-not-allowed">← Sebelumnya</button>
            <button class="px-3 py-1.5 rounded border border-rule bg-white hover:bg-ink-50 text-ink-700/70 transition-colors">Berikutnya →</button>
        </div>
    </div>

</x-app-layout>
